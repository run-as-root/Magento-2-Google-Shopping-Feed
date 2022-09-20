<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\CollectionProvider\LeastLevelCategoryCollectionProvider;
use RunAsRoot\Feed\DataProvider\AllowedCategoryIdsProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\CategoryUrlProvider;

final class CategoryUrlProviderTest extends TestCase
{
    private CategoryUrlProvider $sut;

    /**
     * @var LeastLevelCategoryCollectionProvider|MockObject
     */
    private $leastLevelCategoryCollectionProviderMock;

    /**
     * @var AllowedCategoryIdsProvider|MockObject
     */
    private $categoryIdsProviderMock;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlMock;

    protected function setUp(): void
    {
        $this->leastLevelCategoryCollectionProviderMock = $this->createMock(LeastLevelCategoryCollectionProvider::class);
        $this->categoryIdsProviderMock = $this->createMock(AllowedCategoryIdsProvider::class);
        $this->urlMock = $this->getMockBuilder(UrlInterface::class)->getMock();

        $this->sut = new CategoryUrlProvider(
            $this->leastLevelCategoryCollectionProviderMock,
            $this->categoryIdsProviderMock,
            $this->urlMock
        );
    }

    public function testGet(): void
    {
        $productMock = $this->createMock(Product::class);
        $categoryCollectionMock = $this->createMock(Collection::class);
        $categoryMock = $this->createMock(Category::class);

        $storeId = '100';
        $productMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);

        $productMock->expects($this->once())
            ->method('getCategoryIds')
            ->willReturn(['39', '329', '399']);

        $this->categoryIdsProviderMock
            ->expects($this->once())
            ->method('get')
            ->with((int)$storeId)
            ->willReturn(['11', '39', '41', '43']);

        $this->leastLevelCategoryCollectionProviderMock
            ->expects($this->once())
            ->method('get')
            ->with(['39'], (int)$storeId)
            ->willReturn($categoryCollectionMock);

        $categoryCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn(['39' => $categoryMock]);

        $requestPath = 'waschen-pflegen/speick-naturkosmetik';
        $categoryMock->expects($this->once())
            ->method('getRequestPath')
            ->willReturn($requestPath);

        $categoryUrl = 'https://www.seidenland.de/waschen-pflegen/speick-naturkosmetik';
        $this->urlMock
            ->expects($this->once())
            ->method('getDirectUrl')
            ->with($requestPath, ['_scope' => (int)$storeId])
            ->willReturn($categoryUrl);

        $this->assertEquals($categoryUrl, $this->sut->get($productMock));
    }
}
