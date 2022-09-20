<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\SelectAttributeHandler;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\SelectAttributeHandlerFactory;

final class SelectAttributeHandlerFactoryTest extends TestCase
{
    private SelectAttributeHandlerFactory $sut;

    protected function setUp(): void
    {
        $objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $attributeHandlerMock = $this->createMock(SelectAttributeHandler::class);
        $objectManagerMock->expects($this->once())
            ->method('create')
            ->with(SelectAttributeHandler::class, ['attributeCode' => 'attribute_code'])
            ->willReturn($attributeHandlerMock);
        $this->sut = new SelectAttributeHandlerFactory($objectManagerMock);
    }

    public function testCreate(): void
    {
        $this->sut->create(['attributeCode' => 'attribute_code']);
    }
}
