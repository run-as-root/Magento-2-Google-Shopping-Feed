<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use RunAsRoot\GoogleShoppingFeed\Registry\FeedRegistry;

class ParentProductProvider
{
    public const VARIANT_PARENT_ID = 'run_as_root_feed_variant_parent_id';

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
        $storeId = (int)$product->getStoreId();
        $parentProductId = $this->getParentProductId($product, $storeId);

        if ($parentProductId === null) {
            return $product;
        }

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

    private function getParentProductId(Product $product, int $storeId): ?int
    {
        $variantParentId = $product->getData(self::VARIANT_PARENT_ID);

        if ($variantParentId !== null) {
            return (int)$variantParentId;
        }

        return $this->parentProductIdProvider->get((int)$product->getId(), $storeId);
    }
}
