<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\SelectAttributeHandler;

final class SelectAttributeHandlerTest extends TestCase
{
    private SelectAttributeHandler $sut;

    /**
     * @var ProductResource|MockObject
     */
    private $productResourceMock;

    protected function setUp(): void
    {
        $this->productResourceMock = $this->createMock(ProductResource::class);
        $this->sut = new SelectAttributeHandler($this->productResourceMock, 'attribute_code');
    }

    public function testGet(): void
    {
        $productMock = $this->createMock(Product::class);
        $abstractAttributeMock = $this->createMock(AbstractAttribute::class);
        $abstractSourceMock = $this->createMock(AbstractSource::class);

        $productMock->expects($this->once())
            ->method('getData')
            ->with('attribute_code')
            ->willReturn('attribute_value');

        $this->productResourceMock
            ->expects($this->once())
            ->method('getAttribute')
            ->with('attribute_code')
            ->willReturn($abstractAttributeMock);

        $abstractAttributeMock->expects($this->once())
            ->method('getSource')
            ->willReturn($abstractSourceMock);

        $abstractSourceMock->expects($this->once())
            ->method('getOptionText')
            ->with('attribute_value')
            ->willReturn('Attribute Option Text');

        $this->assertEquals('Attribute Option Text', $this->sut->get($productMock));
    }
}
