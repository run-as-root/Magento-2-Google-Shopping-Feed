<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\SourceModel;

use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\SourceModel\ConfigurableExportType;

final class ConfigurableExportTypeTest extends TestCase
{
    private ConfigurableExportType $sut;

    protected function setUp(): void
    {
        $this->sut = new ConfigurableExportType();
    }

    public function testToOptionArray(): void
    {
        $result = $this->sut->toOptionArray();

        $this->assertCount(2, $result);

        $this->assertEquals(ConfigurableExportType::EXPORT_CHILD_PRODUCTS, $result[0]['value']);
        $this->assertEquals('Only Visible Child Products', (string) $result[0]['label']);

        $this->assertEquals(ConfigurableExportType::EXPORT_PARENT_PRODUCTS, $result[1]['value']);
        $this->assertEquals('Only Parent Products', (string) $result[1]['label']);
    }

    public function testConstants(): void
    {
        $this->assertEquals('child', ConfigurableExportType::EXPORT_CHILD_PRODUCTS);
        $this->assertEquals('parent', ConfigurableExportType::EXPORT_PARENT_PRODUCTS);
    }

    public function testValidOptionValues(): void
    {
        $options = $this->sut->toOptionArray();
        $values = array_column($options, 'value');

        $this->assertContains(ConfigurableExportType::EXPORT_CHILD_PRODUCTS, $values);
        $this->assertContains(ConfigurableExportType::EXPORT_PARENT_PRODUCTS, $values);
    }

    public function testLabelStructure(): void
    {
        $options = $this->sut->toOptionArray();

        foreach ($options as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertIsString($option['value']);
            $this->assertIsObject($option['label']);
        }
    }
}