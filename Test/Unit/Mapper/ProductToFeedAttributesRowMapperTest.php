<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\Mapper;

use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\Data\AttributeConfigData;
use RunAsRoot\Feed\Data\AttributeConfigDataList;
use RunAsRoot\Feed\DataProvider\AttributeHandlerProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\AttributeHandlerInterface;
use RunAsRoot\Feed\Mapper\ProductToFeedAttributesRowMapper;

final class ProductToFeedAttributesRowMapperTest extends TestCase
{
    private ProductToFeedAttributesRowMapper $sut;

    private $attributeHandlerProviderMock;

    protected function setUp(): void
    {
        $this->attributeHandlerProviderMock = $this->createMock(AttributeHandlerProvider::class);
        $this->sut = new ProductToFeedAttributesRowMapper($this->attributeHandlerProviderMock);
    }

    public function testMap(): void
    {
        $productMock = $this->createMock(Product::class);
        $attributeConfigListMock = $this->createMock(AttributeConfigDataList::class);
        $attributeConfigDataOne = new AttributeConfigData();
        $attributeConfigDataOne->setFieldName('gender');
        $attributeConfigDataOne->setAttributeHandler('attribute_handler');

        $attributeConfigDataTwo = new AttributeConfigData();
        $attributeConfigDataTwo->setFieldName('name');
        $attributeConfigDataTwo->setAttributeHandler('attribute_handler');

        $attributeConfigList = [
            $attributeConfigDataOne,
            $attributeConfigDataTwo
        ];

        $attributeConfigListMock
            ->expects($this->once())
            ->method('getList')
            ->willReturn($attributeConfigList);

        $attributeDataProviderOne = $this->createMock(AttributeHandlerInterface::class);
        $attributeDataProviderTwo = $this->createMock(AttributeHandlerInterface::class);

        $this->attributeHandlerProviderMock
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$attributeConfigDataOne],
                [$attributeConfigDataTwo]
            )->willReturnOnConsecutiveCalls(
                $attributeDataProviderOne,
                $attributeDataProviderTwo
            );

        $attributeDataProviderOne->expects($this->once())
            ->method('get')
            ->with($productMock)
            ->willReturn('gender_value');

        $attributeDataProviderTwo->expects($this->once())
            ->method('get')
            ->with($productMock)
            ->willReturn('name_value');

        $expected = [
            'gender' => 'gender_value',
            'name' => 'name_value',
        ];

        $this->assertEquals($expected, $this->sut->map($productMock, $attributeConfigListMock));
    }
}
