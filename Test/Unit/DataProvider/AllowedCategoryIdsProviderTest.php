<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\DataProvider;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\ConfigProvider\FeedConfigProvider;
use RunAsRoot\Feed\DataProvider\AllowedCategoryIdsProvider;
use RunAsRoot\Feed\Registry\FeedRegistry;

final class AllowedCategoryIdsProviderTest extends TestCase
{
    private CollectionFactory $collectionFactory;
    private FeedConfigProvider $configProvider;
    private FeedRegistry $registry;
    private AllowedCategoryIdsProvider $sut;

    protected function setUp(): void
    {
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->configProvider = $this->createMock(FeedConfigProvider::class);
        $this->registry = $this->createMock(FeedRegistry::class);

        $this->sut = new AllowedCategoryIdsProvider(
            $this->collectionFactory,
            $this->configProvider,
            $this->registry
        );
    }

    public function testGetIds(): void
    {
        $expectedResult = [ 1, 11, 12, 111, 2, 3, 31, 312, 3123, 3124 ];
        $storeId = 7;

        $this->registry->expects($this->once())->method('registryForStore')
            ->with('allowed_category_ids', $storeId)
            ->willReturn(null);

        $this->configProvider->method('getCategoryWhitelist')->willReturn([ 1, 2, 3 ]);

        $categories = [
            $this->getCategoryMock(1, [11, 12, 111]),
            $this->getCategoryMock(2, []),
            $this->getCategoryMock(3, [31, 312, 3123, 3124])
        ];
        $collection = $this->createMock(Collection::class);
        $collection->method('getItems')->willReturn($categories);
        $this->collectionFactory->method('create')->willReturn($collection);

        $this->registry->expects($this->once())->method('registerForStore')
            ->with('allowed_category_ids', $storeId, $expectedResult);

        $result = $this->sut->get($storeId);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetIdsWithBlacklist(): void
    {
        $expectedResult = [ 3, 4, 5, 12, 13, 32, 3124, 3125 ];
        $storeId = 8;

        $this->registry->method('registryForStore')->with('allowed_category_ids', $storeId)->willReturn(null);

        $this->configProvider->method('getCategoryWhitelist')->willReturn([ 3, 4, 5 ]);
        $this->configProvider->method('getCategoryBlacklist')->willReturn([ 313, 112 ]);

        $categories = [
            $this->getCategoryMock(3, [12, 13, 112]),
            $this->getCategoryMock(4, []),
            $this->getCategoryMock(5, [32, 313, 3124, 3125])
        ];
        $collection = $this->createMock(Collection::class);
        $collection->method('getItems')->willReturn($categories);
        $this->collectionFactory->method('create')->willReturn($collection);

        $result = $this->sut->get($storeId);

        $this->assertEqualsCanonicalizing($expectedResult, $result);
    }

    public function testGetIdsFromRegistry(): void
    {
        $expectedResult = [ 1, 11, 12, 111, 2, 3, 31, 312, 3123, 3124 ];
        $storeId = 7;

        $this->registry->expects($this->once())->method('registryForStore')
            ->with('allowed_category_ids', $storeId)
            ->willReturn($expectedResult);

        $this->configProvider->expects($this->never())->method('getCategoryWhitelist');

        $this->registry->expects($this->never())->method('registerForStore');

        $result = $this->sut->get($storeId);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetEmptyWhitelist(): void
    {
        $expectedResult = [];
        $storeId = 7;

        $this->configProvider->method('getCategoryWhitelist')->willReturn([]);

        $result = $this->sut->get($storeId);

        $this->assertEquals($expectedResult, $result);
    }

    public function testNoneCategoriesAvailable(): void
    {
        $expectedResult = [];
        $storeId = 7;

        $this->configProvider->method('getCategoryWhitelist')->willReturn([ 1, 2, 3 ]);

        $collection = $this->createMock(Collection::class);
        $collection->method('getItems')->willReturn([]);
        $this->collectionFactory->method('create')->willReturn($collection);

        $result = $this->sut->get($storeId);

        $this->assertEquals($expectedResult, $result);
    }

    private function getCategoryMock(int $id, array $childIds): MockObject
    {
        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn($id);
        $category->method('getChildren')->with(true)->willReturn(
            implode(',', $childIds)
        );

        return $category;
    }
}
