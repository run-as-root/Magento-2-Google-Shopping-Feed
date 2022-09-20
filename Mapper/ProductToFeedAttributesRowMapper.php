<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Mapper;

use Magento\Catalog\Model\Product;
use RunAsRoot\Feed\Data\AttributeConfigDataList;
use RunAsRoot\Feed\DataProvider\AttributeHandlerProvider;
use RunAsRoot\Feed\Exception\HandlerIsNotSpecifiedException;
use RunAsRoot\Feed\Exception\WrongInstanceException;

class ProductToFeedAttributesRowMapper
{
    private AttributeHandlerProvider $handlerProvider;

    public function __construct(AttributeHandlerProvider $handlerProvider)
    {
        $this->handlerProvider = $handlerProvider;
    }

    /**
     * @throws HandlerIsNotSpecifiedException
     * @throws WrongInstanceException
     */
    public function map(Product $product, AttributeConfigDataList $attributesConfigList): array
    {
        $collectedData = [];

        foreach ($attributesConfigList->getList() as $attribute) {
            $attributeDataProvider = $this->handlerProvider->get($attribute);
            $fieldName = $attribute->getFieldName();
            $collectedData[$fieldName] = $attributeDataProvider->get($product);
        }

        return $collectedData;
    }
}
