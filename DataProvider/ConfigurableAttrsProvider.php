<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use RunAsRoot\GoogleShoppingFeed\Registry\FeedRegistry;

class ConfigurableAttrsProvider
{
    private Configurable $configurableType;
    private FeedRegistry $registry;

    public function __construct(FeedRegistry $registry, Configurable $configurableType)
    {
        $this->registry = $registry;
        $this->configurableType = $configurableType;
    }

    public function get(Product $configurableProduct): array
    {
        $registerKey = $configurableProduct->getId() . '|attr';

        if ($this->registry->registry($registerKey)) {
            return $this->registry->registry($registerKey);
        }

        $productAttributeOptions = $this->configurableType->getConfigurableAttributeCollection($configurableProduct);
        $resultArray = [];

        foreach ($productAttributeOptions as $attributeOption) {
            $productAttribute = $attributeOption->getProductAttribute();
            $resultArray[] = [ 'attribute_code' => $productAttribute->getAttributeCode() ];
        }

        $this->registry->register($registerKey, $resultArray);

        return $resultArray;
    }
}
