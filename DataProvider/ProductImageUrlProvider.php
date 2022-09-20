<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider;

use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Framework\Escaper;

class ProductImageUrlProvider
{
    private Escaper $escaper;
    private UrlBuilder $imageUrlBuilder;

    public function __construct(
        Escaper $escaper,
        UrlBuilder $imageUrlBuilder
    ) {
        $this->escaper = $escaper;
        $this->imageUrlBuilder = $imageUrlBuilder;
    }

    public function get(string $imagePath): string
    {
        $imageUrl = $this->imageUrlBuilder->getUrl($imagePath, 'product_page_image_large');
        return $this->escaper->escapeUrl($imageUrl);
    }
}
