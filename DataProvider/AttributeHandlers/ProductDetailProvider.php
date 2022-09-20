<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use RunAsRoot\Feed\DataProvider\ProductAttributeLabelProvider;

class ProductDetailProvider implements AttributeHandlerInterface
{
    private const ATTRIBUTE_CODES = [
        'material_cloth',
        'fill'
    ];

    private ProductAttributeLabelProvider $productAttributeLabelProvider;
    private SimpleAttributeHandlerFactory $attributeHandlerFactory;

    public function __construct(
        ProductAttributeLabelProvider $productAttributeLabelProvider,
        SimpleAttributeHandlerFactory $attributeHandlerFactory
    ) {
        $this->productAttributeLabelProvider = $productAttributeLabelProvider;
        $this->attributeHandlerFactory = $attributeHandlerFactory;
    }

    /**
     * @throws LocalizedException
     */
    public function get(Product $product): array
    {
        $result = [];
        foreach (self::ATTRIBUTE_CODES as $attributeCode) {
            $attributeHandler = $this->attributeHandlerFactory->create(['attributeCode' => $attributeCode]);
            $attributeValue = $attributeHandler->get($product);
            if (!$attributeValue) {
                continue;
            }
            $attributeLabel = $this->productAttributeLabelProvider->get($attributeCode);
            $result[] = [
                'attribute_label' => $attributeLabel,
                'attribute_value' => $attributeValue
            ];
        }
        return $result;
    }
}
