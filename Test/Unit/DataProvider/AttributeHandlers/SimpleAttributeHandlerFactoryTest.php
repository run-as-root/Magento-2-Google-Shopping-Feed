<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\SimpleAttributeHandler;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\SimpleAttributeHandlerFactory;

final class SimpleAttributeHandlerFactoryTest extends TestCase
{
    private SimpleAttributeHandlerFactory $sut;

    protected function setUp(): void
    {
        $objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $attributeHandlerMock = $this->createMock(SimpleAttributeHandler::class);
        $objectManagerMock->expects($this->once())
            ->method('create')
            ->with(SimpleAttributeHandler::class, ['attributeCode' => 'attribute_code'])
            ->willReturn($attributeHandlerMock);
        $this->sut = new SimpleAttributeHandlerFactory($objectManagerMock);
    }

    public function testCreate(): void
    {
        $this->sut->create(['attributeCode' => 'attribute_code']);
    }
}
