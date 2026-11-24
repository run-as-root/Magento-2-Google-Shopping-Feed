<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\CollectionProvider;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\CollectionProvider\ExportableConfigurableParentsCollectionProvider;
use RunAsRoot\GoogleShoppingFeed\CollectionProvider\ProductsCollectionProvider;
use RunAsRoot\GoogleShoppingFeed\Registry\FeedRegistry;

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
    private $exportableParentsSelect;

    /** @var ExportableConfigurableParentsCollectionProvider|MockObject */
    private $exportableParentsCollectionProvider;

    /** @var MetadataPool|MockObject */
    private $metadataPool;

    /** @var array<int, array{method: string, arguments: array<int, mixed>}> */
    private array $collectionCalls = [];

    private ProductsCollectionProvider $sut;

    protected function setUp(): void
    {
        $this->productCollectionFactory = $this->createMock(ProductCollectionFactory::class);
        $this->collection = $this->createMock(ProductCollection::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->collectionSelect = $this->createMock(Select::class);
        $this->exportableParentsSelect = $this->createMock(Select::class);
        $this->exportableParentsCollectionProvider = $this->createMock(
            ExportableConfigurableParentsCollectionProvider::class
        );
        $this->metadataPool = $this->createMock(MetadataPool::class);

        foreach ([ 'addAttributeToSelect', 'addAttributeToFilter', 'addCategoriesFilter', 'addStoreFilter',
            'setPageSize', 'setCurPage', 'addMediaGalleryData', 'addIdFilter' ] as $method) {
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
        $this->productCollectionFactory->method('create')->willReturn($this->collection);

        $exportableParentsCollection = $this->createMock(ProductCollection::class);
        $exportableParentsCollection->method('getSelect')->willReturn($this->exportableParentsSelect);
        $this->exportableParentsCollectionProvider->method('get')->willReturn($exportableParentsCollection);
        $this->exportableParentsSelect->method('reset')->willReturnSelf();
        $this->exportableParentsSelect->method('join')->willReturnSelf();
        $this->exportableParentsSelect->method('distinct')->willReturnSelf();

        $productMetadata = $this->createMock(EntityMetadataInterface::class);
        $productMetadata->method('getLinkField')->willReturn('row_id');
        $this->metadataPool->method('getMetadata')->with(ProductInterface::class)->willReturn($productMetadata);

        $this->sut = new ProductsCollectionProvider(
            $this->productCollectionFactory,
            $this->exportableParentsCollectionProvider,
            $this->metadataPool,
            new FeedRegistry()
        );
    }

    public function testMediaGalleryIsAddedAfterFilteringAndPagination(): void
    {
        $this->connection->method('fetchCol')->willReturn([]);

        $this->sut->get(2, [ 1, 2, 3 ], 1);

        $calledMethods = array_column($this->collectionCalls, 'method');
        $this->assertSame('addMediaGalleryData', end($calledMethods));
        $this->assertSame(1, count(array_keys($calledMethods, 'addMediaGalleryData', true)));
    }

    public function testNotVisibleIndividuallyProductsAreExcluded(): void
    {
        $this->connection->method('fetchCol')->willReturn([]);

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

    public function testStoreFilterIsAppliedBeforeAttributeFilters(): void
    {
        $this->connection->method('fetchCol')->willReturn([]);

        $this->sut->get(1, [ 2 ], 1);

        $calledMethods = array_column($this->collectionCalls, 'method');
        $this->assertSame('addStoreFilter', $calledMethods[0]);
    }

    public function testChildrenOfExportableConfigurablesInTheStoreAreExcluded(): void
    {
        $this->exportableParentsCollectionProvider->expects($this->once())
            ->method('get')
            ->with(3);
        $this->exportableParentsSelect->expects($this->once())
            ->method('reset')
            ->with(Select::COLUMNS);
        $this->exportableParentsSelect->expects($this->once())
            ->method('join')
            ->with(
                [ 'super_link' => 'catalog_product_super_link' ],
                'super_link.parent_id = e.row_id',
                [ 'product_id' ]
            );
        $this->connection->expects($this->once())
            ->method('fetchCol')
            ->with($this->exportableParentsSelect)
            ->willReturn([ '47', '48' ]);

        $this->sut->get(1, [ 2 ], 3);

        $this->assertContains(
            [ 'method' => 'addIdFilter', 'arguments' => [ [ 47, 48 ], true ] ],
            $this->collectionCalls
        );
    }

    public function testChildIdsAreQueriedOncePerStore(): void
    {
        $this->connection->expects($this->exactly(2))
            ->method('fetchCol')
            ->willReturn([ '47' ]);

        $this->sut->get(1, [ 2 ], 3);
        $this->sut->get(2, [ 2 ], 3);
        $this->sut->get(1, [ 2 ], 4);
    }

    public function testNoIdFilterIsAddedWithoutChildrenToExclude(): void
    {
        $this->connection->method('fetchCol')->willReturn([]);

        $this->sut->get(1, [ 2 ], 3);

        $this->assertNotContains('addIdFilter', array_column($this->collectionCalls, 'method'));
    }
}
