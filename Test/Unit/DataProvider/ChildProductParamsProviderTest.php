<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\DataProvider\ChildProductParamsProvider;
use RunAsRoot\Feed\DataProvider\ConfigurableAttrsProvider;

final class ChildProductParamsProviderTest extends TestCase
{
    private ConfigurableAttrsProvider $configurableAttrsProvider;
    private ChildProductParamsProvider $sut;

    protected function setUp(): void
    {
        $this->configurableAttrsProvider = $this->createMock(ConfigurableAttrsProvider::class);
        $this->sut = new ChildProductParamsProvider($this->configurableAttrsProvider);
    }

    public function testGet(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn(Type::TYPE_SIMPLE);
        $configurableProduct = $this->createMock(Product::class);
        $configurableProduct->method('getTypeId')->willReturn(Configurable::TYPE_CODE);

        $productAttributeOptions = [
            [ 'attribute_code' => 'color' ],
            [ 'attribute_code' => 'size' ],
            [ 'attribute_code' => 'other_attribute' ],
        ];
        $this->configurableAttrsProvider->method('get')
            ->with($configurableProduct)
            ->willReturn($productAttributeOptions);

        $product->method('getData')
            ->withConsecutive([ 'color' ], [ 'size' ], [ 'other_attribute' ])
            ->willReturnOnConsecutiveCalls(123, 345, null);

        $expectedResult = [
            'tec_color' => 123,
            'tec_size' => 345,
        ];
        $result = $this->sut->get($product, $configurableProduct);
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetChildProductIsNotSimple(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn(Configurable::TYPE_CODE);
        $configurableProduct = $this->createMock(Product::class);
        $configurableProduct->method('getTypeId')->willReturn(Configurable::TYPE_CODE);

        $expectedResult = null;
        $result = $this->sut->get($product, $configurableProduct);
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetChildConfigurableProductIsNotConfigurable(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn(Type::TYPE_SIMPLE);
        $configurableProduct = $this->createMock(Product::class);
        $configurableProduct->method('getTypeId')->willReturn(Type::TYPE_SIMPLE);

        $expectedResult = null;
        $result = $this->sut->get($product, $configurableProduct);
        $this->assertEquals($expectedResult, $result);
    }
}
