<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Enum;

use RunAsRoot\Feed\Data\AttributeConfigData;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\AdditionalImageLinkProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\CategoryUrlProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\ColorProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\DescriptionProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\EanProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\GenderProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\ImageLinkProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\IsInStockProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\ItemGroupIdProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\ManufacturerProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\MaterialProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\NameProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\PatternProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\PriceProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\ProductDetailProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\ProductTypeProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\ProductUrlProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\ShippingProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\SkuProvider;

interface AttributesToImportEnumInterface
{
    public const ATTRIBUTES = [
        'category_url' => [
            AttributeConfigData::FIELD_NAME => 'category_url',
            AttributeConfigData::ATTRIBUTE_HANDLER => CategoryUrlProvider::class,
        ],
        'color' => [
            AttributeConfigData::FIELD_NAME => 'color',
            AttributeConfigData::ATTRIBUTE_HANDLER => ColorProvider::class,
        ],
        'description' => [
            AttributeConfigData::FIELD_NAME => 'description',
            AttributeConfigData::ATTRIBUTE_HANDLER => DescriptionProvider::class,
        ],
        'ean' => [
            AttributeConfigData::FIELD_NAME => 'ean',
            AttributeConfigData::ATTRIBUTE_HANDLER => EanProvider::class,
        ],
        'gender' => [
            AttributeConfigData::FIELD_NAME => 'gender',
            AttributeConfigData::ATTRIBUTE_HANDLER => GenderProvider::class,
        ],
        'item_group_id' => [
            AttributeConfigData::FIELD_NAME => 'item_group_id',
            AttributeConfigData::ATTRIBUTE_HANDLER => ItemGroupIdProvider::class,
        ],
        'image_link' => [
            AttributeConfigData::FIELD_NAME => 'image_link',
            AttributeConfigData::ATTRIBUTE_HANDLER => ImageLinkProvider::class,
        ],
        'additional_image_link' => [
            AttributeConfigData::FIELD_NAME => 'additional_image_link',
            AttributeConfigData::ATTRIBUTE_HANDLER => AdditionalImageLinkProvider::class,
        ],
        'is_in_stock' => [
            AttributeConfigData::FIELD_NAME => 'is_in_stock',
            AttributeConfigData::ATTRIBUTE_HANDLER => IsInStockProvider::class,
        ],
        'manufacturer' => [
            AttributeConfigData::FIELD_NAME => 'manufacturer',
            AttributeConfigData::ATTRIBUTE_HANDLER => ManufacturerProvider::class,
        ],
        'material' => [
            AttributeConfigData::FIELD_NAME => 'material',
            AttributeConfigData::ATTRIBUTE_HANDLER => MaterialProvider::class,
        ],
        'material_cloth' => [
            AttributeConfigData::FIELD_NAME => 'material_cloth',
            AttributeConfigData::ATTRIBUTE_HANDLER => ManufacturerProvider::class,
        ],
        'name' => [
            AttributeConfigData::FIELD_NAME => 'name',
            AttributeConfigData::ATTRIBUTE_HANDLER => NameProvider::class,
        ],
        'pattern' => [
            AttributeConfigData::FIELD_NAME => 'pattern',
            AttributeConfigData::ATTRIBUTE_HANDLER => PatternProvider::class,
        ],
        'price' => [
            AttributeConfigData::FIELD_NAME => 'price',
            AttributeConfigData::ATTRIBUTE_HANDLER => PriceProvider::class,
        ],
        'product_detail' => [
            AttributeConfigData::FIELD_NAME => 'product_detail',
            AttributeConfigData::ATTRIBUTE_HANDLER => ProductDetailProvider::class,
        ],
        'product_type' => [
            AttributeConfigData::FIELD_NAME => 'product_type',
            AttributeConfigData::ATTRIBUTE_HANDLER => ProductTypeProvider::class,
        ],
        'shipping' => [
            AttributeConfigData::FIELD_NAME => 'shipping',
            AttributeConfigData::ATTRIBUTE_HANDLER => ShippingProvider::class,
        ],
        'sku' => [
            AttributeConfigData::FIELD_NAME => 'sku',
            AttributeConfigData::ATTRIBUTE_HANDLER => SkuProvider::class,
        ],
        'url' => [
            AttributeConfigData::FIELD_NAME => 'url',
            AttributeConfigData::ATTRIBUTE_HANDLER => ProductUrlProvider::class,
        ]
    ];
}
