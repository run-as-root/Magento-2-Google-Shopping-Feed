<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ParentProductProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ProductImageUrlProvider;

class ImageLinkProvider implements AttributeHandlerInterface
{
    private const NO_SELECTION = 'no_selection';

    private ParentProductProvider $parentProductProvider;
    private ProductImageUrlProvider $productImageUrlProvider;
    private StoreManagerInterface $storeManager;

    public function __construct(
        ParentProductProvider $parentProductProvider,
        ProductImageUrlProvider $productImageUrlProvider,
        StoreManagerInterface $storeManager
    ) {
        $this->parentProductProvider = $parentProductProvider;
        $this->productImageUrlProvider = $productImageUrlProvider;
        $this->storeManager = $storeManager;
    }

    public function get(Product $product): string
    {
        $this->storeManager->setCurrentStore($product->getStoreId());

        if ($this->hasImage($product)) {
            return $this->productImageUrlProvider->get($product->getImage());
        }

        $parentProduct = $this->parentProductProvider->get($product);

        if (!$this->hasImage($parentProduct)) {
            return '';
        }

        return $this->productImageUrlProvider->get($parentProduct->getImage());
    }

    private function hasImage(Product $product): bool
    {
        $image = $product->getImage();

        return $image !== null && $image !== '' && $image !== self::NO_SELECTION;
    }
}
