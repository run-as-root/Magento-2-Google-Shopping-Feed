<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductProvider
{
    private CollectionFactory $productCollectionFactory;

    public function __construct(CollectionFactory $productCollectionFactory)
    {
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function get(int $productId, int $storeId): Product
    {
        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('url_key');
        $collection->addAttributeToSelect('image');
        $collection->addFieldToFilter('entity_id', $productId);
        $collection->addStoreFilter($storeId);
        $collection->addMediaGalleryData();
        $collection->load();
        $items = $collection->getItems();
        $values = array_values($items);
        $productForUrlRetrieval = $values[0] ?? null;

        if (!$productForUrlRetrieval instanceof Product) {
            throw new NoSuchEntityException();
        }

        return $productForUrlRetrieval;
    }
}
