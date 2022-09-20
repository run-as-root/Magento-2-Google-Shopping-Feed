<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\CollectionProvider;

use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Data\Collection as DataCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\CollectionProvider\LeastLevelCategoryCollectionProvider;

final class LeastLevelCategoryCollectionProviderTest extends TestCase
{
    /** @var CategoryCollectionFactory|MockObject */
    private $categoryCollectionFactory;

    private LeastLevelCategoryCollectionProvider $sut;

    protected function setUp(): void
    {
        $this->categoryCollectionFactory = $this->createMock(CategoryCollectionFactory::class);

        $this->sut = new LeastLevelCategoryCollectionProvider($this->categoryCollectionFactory);
    }

    public function testGettingCategoryCollection(): void
    {
        $storeId = 7;
        $categoryIds = [ 1, 2, 3 ];

        $collection = $this->createMock(CategoryCollection::class);
        $collection->expects($this->once())
            ->method('addAttributeToSelect')
            ->with('*')
            ->willReturn($collection);
        $collection->expects($this->once())
            ->method('addIsActiveFilter')
            ->willReturn($collection);
        $collection->expects($this->once())
            ->method('addUrlRewriteToResult')
            ->willReturn($collection);
        $collection->expects($this->once())
            ->method('addIdFilter')
            ->with($categoryIds)
            ->willReturn($collection);
        $collection->expects($this->once())
            ->method('setStoreId')
            ->with($storeId)
            ->willReturn($collection);
        $collection->expects($this->once())
            ->method('addOrder')
            ->with('level', DataCollection::SORT_ORDER_ASC)
            ->willReturn($collection);
        $collection->expects($this->once())
            ->method('setPageSize')
            ->with(LeastLevelCategoryCollectionProvider::PAGE_LIMIT)
            ->willReturn($collection);

        $this->categoryCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        $this->sut->get($categoryIds, $storeId);
    }
}
