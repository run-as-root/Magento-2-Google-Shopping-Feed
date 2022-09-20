<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\CollectionProvider;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\CollectionProvider\SimpleProductsCollectionProvider;

final class SimpleProductsCollectionProviderTest extends TestCase
{
    /** @var ProductCollectionFactory|MockObject */
    private $productCollectionFactory;

    private SimpleProductsCollectionProvider $sut;

    protected function setUp(): void
    {
        $this->productCollectionFactory = $this->createMock(ProductCollectionFactory::class);

        $this->sut = new SimpleProductsCollectionProvider($this->productCollectionFactory);
    }

    public function testGettingProductsCollection(): void
    {
        $categories = [ 1, 2, 3 ];
        $storeId = 1;
        $page = 1;
        $collection = $this->createMock(ProductCollection::class);
        $collection->expects($this->once())
            ->method('addAttributeToSelect')
            ->with('*')
            ->willReturn($collection);
        $collection->expects($this->exactly(2))
            ->method('addAttributeToFilter')
            ->withConsecutive(['type_id', 'simple'], ['status', 1])
            ->willReturnSelf();
        $collection->expects($this->once())
            ->method('addCategoriesFilter')
            ->with([ 'in' => $categories ])
            ->willReturn($collection);
        $collection->expects($this->once())
            ->method('addMediaGalleryData')
            ->willReturnSelf();
        $collection->expects($this->once())
            ->method('addStoreFilter')
            ->with($storeId)
            ->willReturn($collection);
        $collection->expects($this->once())
            ->method('setPageSize')
            ->with(SimpleProductsCollectionProvider::BATCH_SIZE)
            ->willReturn($collection);
        $collection->expects($this->once())
            ->method('setCurPage')
            ->with($page)
            ->willReturn($collection);

        $this->productCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        $this->sut->get($page, $categories, $storeId);
    }
}
