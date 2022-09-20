<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\SimpleAttributeHandler;

final class SimpleAttributeHandlerTest extends TestCase
{
    private SimpleAttributeHandler $sut;

    protected function setUp(): void
    {
        $this->sut = new SimpleAttributeHandler('attribute_code');
    }

    public function testGet(): void
    {
        $productMock = $this->createMock(Product::class);

        $productMock->expects($this->once())
            ->method('getData')
            ->with('attribute_code')
            ->willReturn('attribute_value');

        $this->assertEquals('attribute_value', $this->sut->get($productMock));
    }
}
