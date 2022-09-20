<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;

class SimpleAttributeHandler implements AttributeHandlerInterface
{
    private string $attributeCode;

    public function __construct(string $attributeCode)
    {
        $this->attributeCode = $attributeCode;
    }

    public function get(Product $product): string
    {
        return (string)$product->getData($this->attributeCode);
    }
}
