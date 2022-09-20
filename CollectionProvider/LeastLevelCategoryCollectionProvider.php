<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\CollectionProvider;

use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;

class LeastLevelCategoryCollectionProvider
{
    public const PAGE_LIMIT = 1;

    private CategoryCollectionFactory $categoryCollectionFactory;

    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @throws LocalizedException
     */
    public function get(array $categoryIds, int $storeId): CategoryCollection
    {
        /** @var CategoryCollection $collection */
        $collection = $this->categoryCollectionFactory->create();

        $collection->addAttributeToSelect('*')
            ->addIsActiveFilter()
            ->addUrlRewriteToResult()
            ->addIdFilter($categoryIds)
            ->setStoreId($storeId)
            ->addOrder('level', Collection::SORT_ORDER_ASC)
            ->setPageSize(self::PAGE_LIMIT);

        return $collection;
    }
}
