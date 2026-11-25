<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\CollectionProvider;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class ExportableConfigurableParentsCollectionProvider
{
    private ProductCollectionFactory $productCollectionFactory;

    public function __construct(ProductCollectionFactory $productCollectionFactory)
    {
        $this->productCollectionFactory = $productCollectionFactory;
    }

    public function get(int $storeId): ProductCollection
    {
        $collection = $this->productCollectionFactory->create();

        $collection->addStoreFilter($storeId)
            ->addAttributeToFilter('type_id', [ 'eq' => Configurable::TYPE_CODE ])
            ->addAttributeToFilter('status', [ 'eq' => Status::STATUS_ENABLED ])
            ->addAttributeToFilter('visibility', [ 'neq' => Visibility::VISIBILITY_NOT_VISIBLE ]);

        return $collection;
    }
}
