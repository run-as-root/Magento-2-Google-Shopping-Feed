<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\DataProvider\ConfigurableAttrsProvider;
use RunAsRoot\Feed\Registry\FeedRegistry;

final class ConfigurableAttributesProviderTest extends TestCase
{
    /** @var Configurable|MockObject */
    private Configurable $configurableType;
    /** @var FeedRegistry|MockObject */
    private FeedRegistry $registry;
    private ConfigurableAttrsProvider $sut;

    protected function setUp(): void
    {
        $this->configurableType = $this->createMock(Configurable::class);
        $this->registry = $this->createMock(FeedRegistry::class);
        $this->sut = new ConfigurableAttrsProvider($this->registry, $this->configurableType);
    }

    public function testGet(): void
    {
        $id = 2934;
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn($id);

        $registryKey = $id . '|attr';
        $this->registry->expects($this->once())
            ->method('registry')
            ->with($registryKey)
            ->willReturn(null);

        $productAttributeOptions = $this->mockOptionCollection();
        $this->configurableType->method('getConfigurableAttributeCollection')
            ->with($product)
            ->willReturn($productAttributeOptions);

        $expectedResultArray = [
            [ 'attribute_code' => 'color' ],
            [ 'attribute_code' => 'size' ],
        ];

        $this->registry->expects($this->once())
            ->method('register')
            ->with($registryKey, $expectedResultArray);

        $result = $this->sut->get($product);
        $this->assertEquals($expectedResultArray, $result);
    }

    private function mockOptionCollection(): array
    {
        return [
            $this->mockAttrOption('color'),
            $this->mockAttrOption('size'),
        ];
    }

    private function mockAttrOption(string $attrCode): MockObject
    {
        $productAttribute = $this->createMock(AbstractAttribute::class);
        $productAttribute->method('getAttributeCode')->willReturn($attrCode);
        $attributeOption = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->addMethods([ 'getProductAttribute' ])
            ->getMock();
        $attributeOption->method('getProductAttribute')
            ->willReturn($productAttribute);

        return $attributeOption;
    }

    public function testGetFoundInRegistry(): void
    {
        $id = 2934;
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn($id);

        $expectedResultArray = [
            [ 'attribute_code' => 'color' ],
            [ 'attribute_code' => 'size' ],
        ];

        $registryKey = $id . '|attr';
        $this->registry->expects($this->exactly(2))
            ->method('registry')
            ->with($registryKey)
            ->willReturn($expectedResultArray);

        $this->configurableType->expects($this->never())
            ->method('getConfigurableAttributeCollection');

        $this->registry->expects($this->never())
            ->method('register');

        $result = $this->sut->get($product);
        $this->assertEquals($expectedResultArray, $result);
    }
}
