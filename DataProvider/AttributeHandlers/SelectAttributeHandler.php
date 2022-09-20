<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Exception\LocalizedException;

use function is_array;

class SelectAttributeHandler implements AttributeHandlerInterface
{
    private ProductResource $productResource;
    private string $attributeCode;

    public function __construct(
        ProductResource $productResource,
        string $attributeCode
    ) {
        $this->productResource = $productResource;
        $this->attributeCode = $attributeCode;
    }

    /**
     * @throws LocalizedException
     */
    public function get(Product $product): ?string
    {
        $value = $product->getData($this->attributeCode);

        if (!$value) {
            return null;
        }

        $attribute = $this->productResource->getAttribute($this->attributeCode);

        if (!$attribute) {
            return null;
        }

        $result = $attribute->getSource()->getOptionText($value);

        if ($result === false) {
            return null;
        }

        if (!is_array($result)) {
            return (string)$result;
        }

        if (isset($result[$product->getStoreId()])) {
            return (string)$result[$product->getStoreId()];
        }

        $values = array_values($result);
        return isset($values[0]) ? (string)$values[0] : null;
    }
}
