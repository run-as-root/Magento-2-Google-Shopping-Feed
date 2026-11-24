<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\CollectionProvider;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class ConfigurableChildrenCollectionProvider
{
    private ProductCollectionFactory $productCollectionFactory;

    public function __construct(ProductCollectionFactory $productCollectionFactory)
    {
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @param array<int> $childIds
     */
    public function get(array $childIds, int $storeId): ProductCollection
    {
        $collection = $this->productCollectionFactory->create();

        $collection->addStoreFilter($storeId)
            ->addIdFilter($childIds)
            ->addAttributeToSelect('*')
            ->addMediaGalleryData();

        return $collection;
    }
}
