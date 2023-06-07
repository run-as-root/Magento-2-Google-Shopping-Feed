<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Mapper;

use Magento\Catalog\Model\Product;
use RunAsRoot\GoogleShoppingFeed\Data\AttributeConfigDataList;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlerProvider;
use RunAsRoot\GoogleShoppingFeed\Exception\HandlerIsNotSpecifiedException;
use RunAsRoot\GoogleShoppingFeed\Exception\WrongInstanceException;

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
