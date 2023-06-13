<?php

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\ProductTypeProvider;

final class ProductTypeProviderTest extends TestCase
{
    /**
     * @var CategoryRepositoryInterface|MockObject
     */
    private CategoryRepositoryInterface $categoryRepositoryMock;

    /**
     * @var Product|MockObject
     */
    private Product $productMock;

    private ProductTypeProvider $sut;

    protected function setUp(): void
    {
        $this->categoryRepositoryMock = $this->createMock(CategoryRepositoryInterface::class);
        $this->productMock = $this->createMock(Product::class);

        $this->sut = new ProductTypeProvider($this->categoryRepositoryMock);
    }

    public function testProductTypeInCaseOfEmptyCategoryIds(): void
    {
        $this->productMock->method('getCategoryIds')->willReturn([]);
        $this->assertEquals('', $this->sut->get($this->productMock));
    }

    public function testProductTypeInCaseOfNonExistingCategory(): void
    {
        $this->productMock->method('getCategoryIds')->willReturn([1000]);
        $this->categoryRepositoryMock->method('get')->willThrowException(new NoSuchEntityException());
        $this->assertEquals('', $this->sut->get($this->productMock));
    }

    public function testProductTypeInCaseOfExistingCategory(): void
    {
        $this->productMock->method('getCategoryIds')->willReturn([10]);
        $category = $this->createMock(Category::class);

        $this->productMock->method('getStoreId')->willReturn(1);

        $this->categoryRepositoryMock->method('get')->with(10)->willReturn($category);

        $category->method('getPathInStore')->willReturn("10,2");

        $parentCategory = $this->createMock(Category::class);
        $category->method('getParentCategories')->willReturn([10 => $category, 2 => $parentCategory]);

        $category->method('getName')->willReturn('Child Category Name');
        $parentCategory->method('getName')->willReturn('Parent Category Name');

        $this->assertEquals('Parent Category Name > Child Category Name', $this->sut->get($this->productMock));
    }
}
