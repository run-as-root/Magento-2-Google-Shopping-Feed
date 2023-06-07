<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\ProductDetailProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\SimpleAttributeHandler;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\SimpleAttributeHandlerFactory;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ProductAttributeLabelProvider;

final class ProductDetailProviderTest extends TestCase
{
    private ProductDetailProvider $sut;

    /**
     * @var ProductAttributeLabelProvider|MockObject
     */
    private $productAttributeLabelProviderMock;

    /**
     * @var MockObject
     */
    private $simpleAttributeHandlerFactoryMock;

    protected function setUp(): void
    {
        $this->productAttributeLabelProviderMock = $this->createMock(ProductAttributeLabelProvider::class);
        $this->simpleAttributeHandlerFactoryMock = $this->createMock(SimpleAttributeHandlerFactory::class);
        $this->sut = new ProductDetailProvider(
            $this->productAttributeLabelProviderMock,
            $this->simpleAttributeHandlerFactoryMock
        );
    }

    public function testGet(): void
    {
        $productMock = $this->createMock(Product::class);
        $attributeHandlerOne = $this->createMock(SimpleAttributeHandler::class);
        $attributeHandlerTwo = $this->createMock(SimpleAttributeHandler::class);

        $this->simpleAttributeHandlerFactoryMock
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [['attributeCode' => 'material_cloth']],
                [['attributeCode' => 'fill']],
            )
            ->willReturnOnConsecutiveCalls($attributeHandlerOne, $attributeHandlerTwo);

        $attributeHandlerOne->expects($this->once())
            ->method('get')
            ->with($productMock)
            ->willReturn('');

        $attributeHandlerTwo->expects($this->once())
            ->method('get')
            ->with($productMock)
            ->willReturn('100% cotton');

        $this->productAttributeLabelProviderMock
            ->expects($this->once())
            ->method('get')
            ->with('fill')
            ->willReturn('Fill');

        $expected = [
            ['attribute_label' => 'Fill', 'attribute_value' => '100% cotton']
        ];

        $this->assertEquals($expected, $this->sut->get($productMock));
    }
}
