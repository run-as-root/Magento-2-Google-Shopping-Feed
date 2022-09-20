<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\Query\ShippingTableRateQuery;

class ShippingTableRateQueryTest extends TestCase
{
    public ShippingTableRateQuery $sut;

    /** @var ResourceConnection|MockObject */
    private $resourceConnectionMock;

    protected function setUp(): void
    {
        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $this->sut = new ShippingTableRateQuery($this->resourceConnectionMock);
    }

    public function testGet55(): void
    {
        $adapterMock = $this->createMock(AdapterInterface::class);
        $this->resourceConnectionMock
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($adapterMock);

        $selectMock = $this->createMock(Select::class);
        $adapterMock->expects($this->once())
            ->method('select')
            ->willReturn($selectMock);

        $this->resourceConnectionMock
            ->expects($this->once())
            ->method('getTableName')
            ->with('shipping_tablerate')
            ->willReturn('shipping_tablerate');

        $selectMock->expects($this->once())
            ->method('from')
            ->with('shipping_tablerate', '*', null)
            ->willReturnSelf();

        $conditionName = 'package_value';
        $countries = ['CH', 'DE'];
        $selectMock->expects($this->exactly(2))
            ->method('where')
            ->withConsecutive(
                ['condition_name = ?', $conditionName],
                ['dest_country_id in (?)', $countries],
            )
            ->willReturnSelf();

        $this->sut->get($countries, $conditionName);
    }
}
