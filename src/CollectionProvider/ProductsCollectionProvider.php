<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\CollectionProvider;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class ProductsCollectionProvider
{
    public const BATCH_SIZE = 50;
    private const STATUS_ENABLED = 1;
    private const CONFIGURABLE_LINK_TABLE = 'catalog_product_super_link';

    private ProductCollectionFactory $productCollectionFactory;

    public function __construct(
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @param array<int> $categories
     */
    public function get(int $page, array $categories, ?int $storeId = null): ProductCollection
    {
        $collection = $this->productCollectionFactory->create();

        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('status', [ 'eq' => self::STATUS_ENABLED ])
            ->addAttributeToFilter('visibility', [ 'neq' => Visibility::VISIBILITY_NOT_VISIBLE ])
            ->addCategoriesFilter([ 'in' => $categories ])
            ->addStoreFilter($storeId);

        $this->excludeConfigurableChildren($collection);

        $collection->setPageSize(self::BATCH_SIZE)
            ->setCurPage($page)
            ->addMediaGalleryData();

        return $collection;
    }

    private function excludeConfigurableChildren(ProductCollection $collection): void
    {
        $connection = $collection->getConnection();
        $configurableChildrenSelect = $connection->select()->from(
            $collection->getTable(self::CONFIGURABLE_LINK_TABLE),
            'product_id'
        );

        $collection->getSelect()->where(
            $connection->prepareSqlCondition('e.entity_id', [ 'nin' => $configurableChildrenSelect ])
        );
    }
}
