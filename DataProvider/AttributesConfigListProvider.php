<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider;

use InvalidArgumentException;
use RunAsRoot\GoogleShoppingFeed\Data\AttributeConfigData;
use RunAsRoot\GoogleShoppingFeed\Data\AttributeConfigDataFactory;
use RunAsRoot\GoogleShoppingFeed\Data\AttributeConfigDataList;
use RunAsRoot\GoogleShoppingFeed\Enum\AttributesToImportEnumInterface;

class AttributesConfigListProvider
{
    private AttributeConfigDataFactory $attributeDataFactory;

    public function __construct(AttributeConfigDataFactory $attributeDataFactory)
    {
        $this->attributeDataFactory = $attributeDataFactory;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function get(): AttributeConfigDataList
    {
        $attributes = [];

        foreach (AttributesToImportEnumInterface::ATTRIBUTES as $attributeConfig) {
            $attributes[] = $this->attributeDataFactory->create(
                [
                    'data' => [
                        AttributeConfigData::FIELD_NAME => $attributeConfig[ AttributeConfigData::FIELD_NAME ],
                        AttributeConfigData::ATTRIBUTE_HANDLER =>
                            $attributeConfig [ AttributeConfigData::ATTRIBUTE_HANDLER ],
                    ],
                ],
            );
        }

        return new AttributeConfigDataList($attributes);
    }
}
