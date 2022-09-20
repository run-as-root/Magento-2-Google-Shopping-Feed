<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class ChildProductParamsProvider
{
    private ConfigurableAttrsProvider $configurableAttrsProvider;

    public function __construct(ConfigurableAttrsProvider $configurableAttrsProvider)
    {
        $this->configurableAttrsProvider = $configurableAttrsProvider;
    }

    public function get(Product $product, Product $configurableProduct): ?array
    {
        if (
            $product->getTypeId() !== Type::TYPE_SIMPLE ||
            $configurableProduct->getTypeId() !== Configurable::TYPE_CODE
        ) {
            return null;
        }

        $productAttributeOptions = $this->configurableAttrsProvider->get($configurableProduct);
        $attributeOptions = [];

        foreach ($productAttributeOptions as $productAttribute) {
            $attributeCode = $productAttribute['attribute_code'];
            $attributeValue = $product->getData($attributeCode);

            if (!$attributeValue) {
                continue;
            }
            // TODO
            $attributeOptions['tec_' . $attributeCode] = $attributeValue;
        }

        return $attributeOptions;
    }
}
