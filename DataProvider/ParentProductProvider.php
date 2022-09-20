<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Exception\NoSuchEntityException;
use RunAsRoot\GoogleShoppingFeed\Registry\FeedRegistry;

class ParentProductProvider
{
    private FeedRegistry $registry;
    private ProductProvider $productProvider;
    private ParentProductIdProvider $parentProductIdProvider;

    public function __construct(
        FeedRegistry          $registry,
        ProductProvider       $productProvider,
        ParentProductIdProvider $parentProductIdProvider
    ) {
        $this->registry = $registry;
        $this->productProvider = $productProvider;
        $this->parentProductIdProvider = $parentProductIdProvider;
    }

    public function get(Product $product): Product
    {
        if ($product->getTypeId() !== Type::TYPE_SIMPLE) {
            return $product;
        }

        $parentProductId = $this->parentProductIdProvider->get((int)$product->getId());

        if ($parentProductId === null) {
            return $product;
        }

        $storeId = (int)$product->getStoreId();
        $parentProductRegistryKey = (string)$parentProductId;

        if ($this->registry->registryForStore($parentProductRegistryKey, $storeId) !== null) {
            return $this->registry->registryForStore($parentProductRegistryKey, $storeId);
        }

        try {
            $productForUrlRetrieval = $this->productProvider->get($parentProductId, $storeId);
        } catch (NoSuchEntityException $exception) {
            $productForUrlRetrieval = $product;
        }

        $this->registry->registerForStore($parentProductRegistryKey, $storeId, $productForUrlRetrieval);

        return $productForUrlRetrieval;
    }
}
