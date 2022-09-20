<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\DataProvider;

use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Exception\LocalizedException;

class ProductAttributeLabelProvider
{
    private ProductResource $productResource;

    public function __construct(
        ProductResource $productResource
    ) {
        $this->productResource = $productResource;
    }

    /**
     * @throws LocalizedException
     */
    public function get(string $attributeCode): string
    {
        return $this->productResource
            ->getAttribute($attributeCode)
            ->getFrontend()
            ->getLabel();
    }
}
