<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Data;

use Magento\Framework\DataObject;

class AttributeConfigData extends DataObject
{
    public const FIELD_NAME = 'field_name';
    public const ATTRIBUTE_HANDLER = 'attribute_handler';

    public function getFieldName(): ?string
    {
        return $this->getData(self::FIELD_NAME) === null ? null
            : (string)$this->getData(self::FIELD_NAME);
    }

    public function setFieldName(?string $fieldName): void
    {
        $this->setData(self::FIELD_NAME, $fieldName);
    }

    public function getAttributeHandler(): ?string
    {
        return $this->getData(self::ATTRIBUTE_HANDLER);
    }

    public function setAttributeHandler(?string $attributeHandler): void
    {
        $this->setData(self::ATTRIBUTE_HANDLER, $attributeHandler);
    }
}
