<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\DataProvider;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use RunAsRoot\Feed\ConfigProvider\FeedConfigProvider;
use RunAsRoot\Feed\Registry\FeedRegistry;

class AllowedCategoryIdsProvider
{
    private const REGISTRY_KEY = 'allowed_category_ids';

    private CollectionFactory $collectionFactory;
    private FeedConfigProvider $configProvider;
    private FeedRegistry $registry;

    public function __construct(
        CollectionFactory $collectionFactory,
        FeedConfigProvider $configProvider,
        FeedRegistry $registry
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->configProvider = $configProvider;
        $this->registry = $registry;
    }

    public function get(int $storeId): array
    {
        $registryValue = $this->registry->registryForStore(self::REGISTRY_KEY, $storeId);

        if ($registryValue !== null) {
            return $registryValue;
        }

        $ids = $this->getAllowedCategoryIds($storeId);
        $this->registry->registerForStore(self::REGISTRY_KEY, $storeId, $ids);

        return $ids;
    }

    private function getAllowedCategoryIds(int $storeId): array
    {
        $categoriesWhiteListIds = $this->configProvider->getCategoryWhitelist($storeId);

        if (count($categoriesWhiteListIds) === 0) {
            return [];
        }

        $categoryIds = [];

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('entity_id', [ 'in' => $categoriesWhiteListIds ]);

        foreach ($collection->getItems() as $category) {
            $categoryIds[] = $category->getId();
            $childCategories = $category->getChildren(true);

            if (empty($childCategories)) {
                continue;
            }

            $childCategoryIds = explode(',', $childCategories);
            $categoryIds = $this->mergeCategoryIds($categoryIds, $childCategoryIds);
        }

        return $this->unsetBlacklistedCategories($categoryIds, $storeId);
    }

    private function unsetBlacklistedCategories(array $categoryIds, int $storeId): array
    {
        $categoriesBlackListIds = $this->configProvider->getCategoryBlacklist($storeId);

        foreach ($categoriesBlackListIds as $categoryId) {
            $key = array_search($categoryId, $categoryIds);

            if ($key === false) {
                continue;
            }

            unset($categoryIds[$key]);
        }

        return $categoryIds;
    }

    private function mergeCategoryIds(array $categoryIds, array $childCategoryIds): array
    {
        return array_merge($categoryIds, $childCategoryIds);
    }
}
