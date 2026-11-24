<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\CollectionProvider;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\CollectionProvider\ProductsCollectionProvider;

final class ProductsCollectionProviderTest extends TestCase
{
    /** @var ProductCollectionFactory|MockObject */
    private $productCollectionFactory;

    /** @var ProductCollection|MockObject */
    private $collection;

    /** @var AdapterInterface|MockObject */
    private $connection;

    /** @var Select|MockObject */
    private $collectionSelect;

    /** @var Select|MockObject */
    private $configurableChildrenSelect;

    /** @var array<int, array{method: string, arguments: array<int, mixed>}> */
    private array $collectionCalls = [];

    private ProductsCollectionProvider $sut;

    protected function setUp(): void
    {
        $this->productCollectionFactory = $this->createMock(ProductCollectionFactory::class);
        $this->collection = $this->createMock(ProductCollection::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->collectionSelect = $this->createMock(Select::class);
        $this->configurableChildrenSelect = $this->createMock(Select::class);

        foreach ([ 'addAttributeToSelect', 'addAttributeToFilter', 'addCategoriesFilter', 'addStoreFilter',
            'setPageSize', 'setCurPage', 'addMediaGalleryData' ] as $method) {
            $this->collection->method($method)->willReturnCallback(
                function (...$arguments) use ($method) {
                    $this->collectionCalls[] = [ 'method' => $method, 'arguments' => $arguments ];

                    return $this->collection;
                }
            );
        }

        $this->collection->method('getConnection')->willReturn($this->connection);
        $this->collection->method('getSelect')->willReturn($this->collectionSelect);
        $this->collection->method('getTable')->willReturnArgument(0);
        $this->connection->method('select')->willReturn($this->configurableChildrenSelect);
        $this->configurableChildrenSelect->method('from')->willReturnSelf();
        $this->productCollectionFactory->method('create')->willReturn($this->collection);

        $this->sut = new ProductsCollectionProvider($this->productCollectionFactory);
    }

    public function testMediaGalleryIsAddedAfterFilteringAndPagination(): void
    {
        $this->sut->get(2, [ 1, 2, 3 ], 1);

        $calledMethods = array_column($this->collectionCalls, 'method');
        $this->assertSame('addMediaGalleryData', end($calledMethods));
        $this->assertSame(1, count(array_keys($calledMethods, 'addMediaGalleryData', true)));
    }

    public function testNotVisibleIndividuallyProductsAreExcluded(): void
    {
        $this->sut->get(1, [ 2 ], 1);

        $attributeFilters = array_map(
            static fn (array $call): array => array_slice($call['arguments'], 0, 2),
            array_filter(
                $this->collectionCalls,
                static fn (array $call): bool => $call['method'] === 'addAttributeToFilter'
            )
        );

        $this->assertContains(
            [ 'visibility', [ 'neq' => Visibility::VISIBILITY_NOT_VISIBLE ] ],
            $attributeFilters
        );
    }

    public function testConfigurableChildrenAreExcluded(): void
    {
        $this->configurableChildrenSelect->expects($this->once())
            ->method('from')
            ->with('catalog_product_super_link', 'product_id');
        $this->connection->expects($this->once())
            ->method('prepareSqlCondition')
            ->with('e.entity_id', [ 'nin' => $this->configurableChildrenSelect ])
            ->willReturn('e.entity_id NOT IN (configurable children)');
        $this->collectionSelect->expects($this->once())
            ->method('where')
            ->with('e.entity_id NOT IN (configurable children)');

        $this->sut->get(1, [ 2 ], 1);
    }
}
