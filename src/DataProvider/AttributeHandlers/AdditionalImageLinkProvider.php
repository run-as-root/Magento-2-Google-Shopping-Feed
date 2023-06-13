<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ParentProductProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ProductImageUrlProvider;

class AdditionalImageLinkProvider implements AttributeHandlerInterface
{
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

    /**
     * @throws LocalizedException
     */
    public function get(Product $product): array
    {
        $mainImage = $product->getImage();
        $mediaGallery = $product->getMediaGalleryEntries();

        if (empty($mediaGallery)) {
            $parentProduct = $this->parentProductProvider->get($product);
            $mainImage = $parentProduct->getImage();
            $mediaGallery = $parentProduct->getMediaGalleryEntries();
        }

        if (empty($mediaGallery)) {
            return [];
        }

        $this->storeManager->setCurrentStore($product->getStoreId());

        $imageLinks = [];

        foreach ($mediaGallery as $mediaGalleryEntry) {
            if (
                $mediaGalleryEntry->getFile() === $mainImage ||
                !$mediaGalleryEntry->getFile()
            ) {
                continue;
            }

            $imageLinks[] = $this->productImageUrlProvider->get($mediaGalleryEntry->getFile());
        }

        return $imageLinks;
    }
}
