<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\Converter;

use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\Converter\ArrayToXmlConverter;

final class ArrayToXmlConverterTest extends TestCase
{
    private ArrayToXmlConverter $sut;

    protected function setUp(): void
    {
        $dateTimeMock = $this->createMock(DateTime::class);

        $dateTimeMock->expects($this->once())
            ->method('gmtDate')
            ->with('Y-m-d H:i')
            ->willReturn('2022-04-13 18:23');

        $this->sut = new ArrayToXmlConverter($dateTimeMock);
    }

    public function testConvert(): void
    {
        $this->assertXmlStringEqualsXmlString($this->getExpectedResult(), $this->sut->convert($this->getProductRow()));
    }

    private function getProductRow(): array
    {
        return [
            [
                'category_url' => 'https://app.seidenland.test/bettdecken/naturhaardecken',
                'color' => null,
                'description' => 'product-description',
                'ean' => '4000863525821',
                'gender' => null,
                'item_group_id' => 'FAN-53KBBW01V0011',
                'image_link' => 'https://app.seidenland.test/media/catalog/product/cache/775fcb5986783027a0f3aac116b4fff3/c/a/cashmere-duo-winterdecke-53kbbw01v0009-frankenstolz-2_2.jpg',
                'additional_image_link' => [
                    0 => 'https://app.seidenland.test/media/catalog/product/cache/775fcb5986783027a0f3aac116b4fff3/o/e/oeko-tex-zertifizierte-schadstoffkontrollierte-bettwaren-frankenstolz_3_2_1_2_2_4_1_1_3_2_2_2_2.jpg',
                    1 => 'https://app.seidenland.test/media/catalog/product/cache/775fcb5986783027a0f3aac116b4fff3/f/a/fan-bettwaren-made-in-germany_2_2_1_2_2_4_1_1_3_2_2_2_2.jpg'
                ],
                'is_in_stock' => 'in_stock',
                'manufacturer' => 'f.a.n. Frankenstolz',
                'material' => 'Kaschmir',
                'material_cloth' => 'f.a.n. Frankenstolz',
                'name' => 'Frankenstolz Duo Bettdecke 90% CASHMERE extra warm 40°C waschbar-155/220',
                'pattern' => null,
                'price' => '230,00 EUR',
                'product_detail' => [
                    0 => [
                        'attribute_label' => 'Material Stoff',
                        'attribute_value' => '100% Baumwolle (Batist)',
                    ],
                    1 => [
                        'attribute_label' => 'Material Füllung',
                        'attribute_value' => '90% Kaschmir, 10% Baumwolle',
                    ],
                ],
                'product_type' => 'Duo- Bettdecke',
                'shipping' => [
                    0 => [
                        'country' => 'CH',
                        'price' => '23,68 EUR',
                    ],
                    1 => [
                        'country' => 'DE',
                        'price' => '4,95 EUR',
                    ],
                    2 => [
                        'country' => 'LI',
                        'price' => '23,68 EUR',
                    ],
                    3 => [
                        'country' => 'NO',
                        'price' => '23,68 EUR',
                    ],
                ],
                'sku' => 'FAN-53KBBW01V0011-155/220',
                'url' => 'https://app.seidenland.test/cashmere-90-duo-bettdecke-extra-warm-frankenstolz?tec_size_bettwaren_config=1113',
            ]
        ];
    }

    private function getExpectedResult(): string
    {
        return <<<XML
<?xml version="1.0"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
	<channel>
        <created_at>2022-04-13 18:23</created_at>
        <item>
			<g:id><![CDATA[FAN-53KBBW01V0011-155/220]]></g:id>
			<g:title><![CDATA[Frankenstolz Duo Bettdecke 90% CASHMERE extra warm 40°C waschbar-155/220]]></g:title>
			<g:description><![CDATA[product-description]]></g:description>
			<g:link><![CDATA[https://app.seidenland.test/cashmere-90-duo-bettdecke-extra-warm-frankenstolz?tec_size_bettwaren_config=1113]]></g:link>
			<g:image_link><![CDATA[https://app.seidenland.test/media/catalog/product/cache/775fcb5986783027a0f3aac116b4fff3/c/a/cashmere-duo-winterdecke-53kbbw01v0009-frankenstolz-2_2.jpg]]></g:image_link>
			<g:availability><![CDATA[in_stock]]></g:availability>
            <g:product_type><![CDATA[Duo- Bettdecke]]></g:product_type>
			<g:price><![CDATA[230,00 EUR]]></g:price>
			<g:brand><![CDATA[f.a.n. Frankenstolz]]></g:brand>
			<g:gtin><![CDATA[4000863525821]]></g:gtin>
            <g:mpn><![CDATA[FAN-53KBBW01V0011-155/220]]></g:mpn>
            <g:color><![CDATA[]]></g:color>
            <g:gender><![CDATA[]]></g:gender>
            <g:material><![CDATA[Kaschmir]]></g:material>
            <g:pattern><![CDATA[]]></g:pattern>
            <g:item_group_id><![CDATA[FAN-53KBBW01V0011]]></g:item_group_id>
            <g:product_detail>
                <g:section_name><![CDATA[General]]></g:section_name>
                <g:attribute_name><![CDATA[Material Stoff]]></g:attribute_name>
                <g:attribute_value><![CDATA[100% Baumwolle (Batist)]]></g:attribute_value>
            </g:product_detail>
            <g:product_detail>
                <g:section_name><![CDATA[General]]></g:section_name>
                <g:attribute_name><![CDATA[Material Füllung]]></g:attribute_name>
                <g:attribute_value><![CDATA[90% Kaschmir, 10% Baumwolle]]></g:attribute_value>
            </g:product_detail>
            <g:shipping>
                <g:country><![CDATA[CH]]></g:country>
                <g:price><![CDATA[23,68 EUR]]></g:price>
             </g:shipping>
            <g:shipping>
                <g:country><![CDATA[DE]]></g:country>
                <g:price><![CDATA[4,95 EUR]]></g:price>
             </g:shipping>
            <g:shipping>
                <g:country><![CDATA[LI]]></g:country>
                <g:price><![CDATA[23,68 EUR]]></g:price>
             </g:shipping>
            <g:shipping>
                <g:country><![CDATA[NO]]></g:country>
                <g:price><![CDATA[23,68 EUR]]></g:price>
             </g:shipping>
			<g:additional_image_link><![CDATA[https://app.seidenland.test/media/catalog/product/cache/775fcb5986783027a0f3aac116b4fff3/o/e/oeko-tex-zertifizierte-schadstoffkontrollierte-bettwaren-frankenstolz_3_2_1_2_2_4_1_1_3_2_2_2_2.jpg]]></g:additional_image_link><g:additional_image_link><![CDATA[https://app.seidenland.test/media/catalog/product/cache/775fcb5986783027a0f3aac116b4fff3/f/a/fan-bettwaren-made-in-germany_2_2_1_2_2_4_1_1_3_2_2_2_2.jpg]]></g:additional_image_link>
		</item>
    </channel>
</rss>
XML;
    }
}
