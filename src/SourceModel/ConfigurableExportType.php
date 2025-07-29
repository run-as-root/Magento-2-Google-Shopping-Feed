<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\SourceModel;

use Magento\Framework\Data\OptionSourceInterface;

class ConfigurableExportType implements OptionSourceInterface
{
    public const EXPORT_CHILD_PRODUCTS = 'child';
    public const EXPORT_PARENT_PRODUCTS = 'parent';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::EXPORT_CHILD_PRODUCTS,
                'label' => __('Only Visible Child Products'),
            ],
            [
                'value' => self::EXPORT_PARENT_PRODUCTS,
                'label' => __('Only Parent Products'),
            ],
        ];
    }
}
