<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\DataProvider;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\Data\AttributeConfigData;
use RunAsRoot\Feed\Data\AttributeConfigDataFactory;
use RunAsRoot\Feed\Data\AttributeConfigDataList;
use RunAsRoot\Feed\DataProvider\AttributesConfigListProvider;
use RunAsRoot\Feed\Enum\AttributesToImportEnumInterface;

final class AttributesConfigListProviderTest extends TestCase
{
    /** @var MockObject|AttributeConfigDataFactory  */
    private $attributeDataFactoryMock;

    private AttributesConfigListProvider $sut;

    protected function setUp(): void
    {
        $this->attributeDataFactoryMock = $this->createMock(AttributeConfigDataFactory::class);

        $this->sut = new AttributesConfigListProvider($this->attributeDataFactoryMock);
    }

    /**
     * @dataProvider getExpectedAttributes
     */
    public function testGetAttributes(
        array $feedColumns,
        array $attributeDataConsecutive,
        array $attributeReturn
    ): void {
        $this->attributeDataFactoryMock->expects($this->exactly(count($feedColumns)))
            ->method('create')
            ->withConsecutive(...$attributeDataConsecutive)
            ->willReturnOnConsecutiveCalls(...$attributeReturn);

        $result = $this->sut->get();

        $this->assertInstanceOf(AttributeConfigDataList::class, $result);

        $getRowByPosition = static function (array $list, int $position) {
            $keys = array_keys($list);
            $keyByPosition = $keys[$position];
            return $list[$keyByPosition];
        };

        foreach ($result->getList() as $key => $item) {
            $this->assertInstanceOf(AttributeConfigData::class, $item);
            $row = $getRowByPosition($feedColumns, $key);
            $this->assertEquals($row[ AttributeConfigData::FIELD_NAME ], $item->getFieldName());
            $this->assertEquals($row[ AttributeConfigData::ATTRIBUTE_HANDLER ], $item->getAttributeHandler());
        }
    }

    public function getExpectedAttributes(): array
    {
        $feedColumns = AttributesToImportEnumInterface::ATTRIBUTES;

        $attributeDataConsecutive = [];
        $attributeReturn = [];

        foreach ($feedColumns as $feedColumn) {
            $dataArray = [
                AttributeConfigData::FIELD_NAME => $feedColumn[ AttributeConfigData::FIELD_NAME ],
                AttributeConfigData::ATTRIBUTE_HANDLER => $feedColumn[ AttributeConfigData::ATTRIBUTE_HANDLER ],
            ];
            $attributeDataConsecutive[] = [
                [
                    'data' => $dataArray,
                ],
            ];

            // mock factory
            $attributeReturn[] = new AttributeConfigData($dataArray);
        }

        return [
           'Test getting attributes returning correct data' => [
                $feedColumns,
                $attributeDataConsecutive,
                $attributeReturn
            ]
        ];
    }
}
