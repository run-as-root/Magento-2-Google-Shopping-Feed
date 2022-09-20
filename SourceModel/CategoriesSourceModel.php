<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\SourceModel;

use Magento\Catalog\Api\CategoryListInterface;
use Magento\Catalog\Api\Data\CategorySearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;

class CategoriesSourceModel implements OptionSourceInterface
{
    private CategoryListInterface $categoryList;
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    public function __construct(CategoryListInterface $categoryList, SearchCriteriaBuilder $searchCriteriaBuilder)
    {
        $this->categoryList = $categoryList;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function toOptionArray(): array
    {
        $searchCriteria = $this->searchCriteriaBuilder;
        $categories = $this->categoryList->getList($searchCriteria->create());

        return $this->getCategoriesOptions($categories);
    }

    private function getCategoriesOptions(CategorySearchResultsInterface $categories): array
    {
        $options = [];

        foreach ($categories->getItems() as $category) {
            $options[] = [
                'value' => $category->getId(),
                'label' => $category->getName() . " (ID: {$category->getId()})",
            ];
        }

        return $options;
    }
}
