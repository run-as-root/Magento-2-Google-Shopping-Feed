<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\SourceModel;

use Magento\Catalog\Api\CategoryListInterface;
use Magento\Catalog\Api\Data\CategorySearchResultsInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\SourceModel\CategoriesSourceModel;

final class CategoriesSourceModelTest extends TestCase
{
    /** @var CategoryListInterface|mixed|MockObject  */
    private CategoryListInterface $categoryList;
    /** @var SearchCriteriaBuilder|mixed|MockObject  */
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private CategoriesSourceModel $sut;

    protected function setUp(): void
    {
        $this->categoryList = $this->createMock(CategoryListInterface::class);
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteriaBuilder->method('create')->willReturn($searchCriteria);
        $this->sut = new CategoriesSourceModel($this->categoryList, $this->searchCriteriaBuilder);
    }

    public function testToOptionArray(): void
    {
        $categoryData = [
            [
                'id' => 1,
                'name' => 'first',
            ],
            [
                'id' => 2,
                'name' => 'second',
            ],
            [
                'id' => 3,
                'name' => 'third',
            ],
        ];

        $categoriesList = [
            $this->createCategoryMock($categoryData[0]),
            $this->createCategoryMock($categoryData[1]),
            $this->createCategoryMock($categoryData[2]),
        ];

        $categorySearchResult = $this->createMock(CategorySearchResultsInterface::class);
        $categorySearchResult->method('getItems')->willReturn($categoriesList);
        $this->categoryList->expects($this->once())->method('getList')->willReturn($categorySearchResult);

        $result = $this->sut->toOptionArray();

        foreach ($categoryData as $key => $data) {
            $resultData = $result[$key];

            $expectedName = $data['name'] . ' (ID: ' . $data['id'] .')';

            $this->assertEquals($data['id'], $resultData['value']);
            $this->assertEquals($expectedName, $resultData['label']);
        }
    }

    private function createCategoryMock(array $data): MockObject
    {
        $id = $data['id'];
        $name = $data['name'];

        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn($id);
        $category->method('getName')->willReturn($name);

        return $category;
    }
}
