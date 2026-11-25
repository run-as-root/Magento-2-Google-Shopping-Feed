<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Registry;

use Magento\Catalog\Model\Product;
use RunAsRoot\GoogleShoppingFeed\Data\ProductPricesData;

/**
 * Holds the prices of one product only, so the feed's memory use does not grow with the catalog size.
 */
class LastComputedProductPricesCache
{
    private ?Product $product = null;
    private ?int $productId = null;
    private ?int $storeId = null;
    private ?ProductPricesData $prices = null;

    public function get(Product $product): ?ProductPricesData
    {
        if (!$this->isComputedFor($product)) {
            return null;
        }

        return $this->prices;
    }

    public function save(Product $product, ProductPricesData $prices): void
    {
        $this->product = $product;
        $this->productId = (int)$product->getId();
        $this->storeId = (int)$product->getStoreId();
        $this->prices = $prices;
    }

    private function isComputedFor(Product $product): bool
    {
        return $this->product === $product
            && $this->productId === (int)$product->getId()
            && $this->storeId === (int)$product->getStoreId();
    }
}
