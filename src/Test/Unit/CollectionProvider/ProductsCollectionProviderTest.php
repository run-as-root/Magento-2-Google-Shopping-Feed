<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\CollectionProvider;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\CollectionProvider\ProductsCollectionProvider;

final class ProductsCollectionProviderTest extends TestCase
{
    /** @var ProductCollectionFactory|MockObject */
    private $productCollectionFactory;

    private ProductsCollectionProvider $sut;

    protected function setUp(): void
    {
        $this->productCollectionFactory = $this->createMock(ProductCollectionFactory::class);

        $this->sut = new ProductsCollectionProvider($this->productCollectionFactory);
    }

    public function testMediaGalleryIsAddedAfterFilteringAndPagination(): void
    {
        $categories = [ 1, 2, 3 ];
        $storeId = 1;
        $page = 2;
        $calls = [];
        $collection = $this->createMock(ProductCollection::class);

        foreach (['addAttributeToSelect', 'addAttributeToFilter', 'addCategoriesFilter', 'addStoreFilter',
            'setPageSize', 'setCurPage', 'addMediaGalleryData'] as $method) {
            $collection->method($method)->willReturnCallback(
                static function () use (&$calls, $method, $collection) {
                    $calls[] = $method;

                    return $collection;
                }
            );
        }

        $this->productCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        $this->sut->get($page, $categories, $storeId);

        $this->assertSame('addMediaGalleryData', end($calls));
        $this->assertSame(1, count(array_keys($calls, 'addMediaGalleryData', true)));
    }
}
