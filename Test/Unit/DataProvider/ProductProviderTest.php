<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ProductProvider;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

final class ProductProviderTest extends TestCase
{
    /** @var CollectionFactory|MockObject */
    private CollectionFactory $productCollectionFactory;
    private ProductProvider $sut;

    protected function setUp(): void
    {
        $this->productCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->sut = new ProductProvider($this->productCollectionFactory);
    }

    public function testGet(): void
    {
        $id = 2431;
        $storeId = 9;

        $product1 = $this->createMock(Product::class);
        $product2 = $this->createMock(Product::class);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->exactly(2))->method('addAttributeToSelect')->withConsecutive(['url_key'],['image']);
        $collection->expects($this->once())->method('addFieldToFilter')->with('entity_id', $id);
        $collection->expects($this->once())->method('addStoreFilter')->with($storeId);
        $collection->expects($this->once())->method('load');
        $collection->expects($this->once())->method('getItems')->willReturn([ $product1, $product2 ]);

        $this->productCollectionFactory->method('create')->willReturn($collection);

        $res = $this->sut->get($id, $storeId);
        $this->assertEquals($product1, $res);
    }

    public function testGetNotFound(): void
    {
        $id = 2431;
        $storeId = 9;

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->exactly(2))->method('addAttributeToSelect')->withConsecutive(['url_key'],['image']);
        $collection->expects($this->once())->method('addFieldToFilter')->with('entity_id', $id);
        $collection->expects($this->once())->method('addStoreFilter')->with($storeId);
        $collection->expects($this->once())->method('load');
        $collection->expects($this->once())->method('getItems')->willReturn([]);

        $this->productCollectionFactory->method('create')->willReturn($collection);

        $this->expectException(NoSuchEntityException::class);

        $res = $this->sut->get($id, $storeId);
    }
}
