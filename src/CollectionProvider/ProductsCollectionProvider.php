<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\CollectionProvider;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class ProductsCollectionProvider
{
    public const BATCH_SIZE = 50;
    private const STATUS_ENABLED = 1;

    private ProductCollectionFactory $productCollectionFactory;

    public function __construct(
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
    }

    public function get(int $page, array $categories, int $storeId = null): ProductCollection
    {
        $collection = $this->productCollectionFactory->create();

        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('status', self::STATUS_ENABLED)
            ->addCategoriesFilter([ 'in' => $categories ])
            ->addMediaGalleryData()
            ->addStoreFilter($storeId)
            ->setPageSize(self::BATCH_SIZE)
            ->setCurPage($page);

        return $collection;
    }
}
