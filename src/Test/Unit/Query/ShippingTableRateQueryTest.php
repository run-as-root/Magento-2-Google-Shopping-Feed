<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\Query\ShippingTableRateQuery;

class ShippingTableRateQueryTest extends TestCase
{
    public ShippingTableRateQuery $sut;

    /** @var ResourceConnection|MockObject */
    private $resourceConnectionMock;

    /** @var Select|MockObject */
    private $selectMock;

    /** @var array<array{string, mixed}> */
    private array $whereConditions = [];

    protected function setUp(): void
    {
        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $this->sut = new ShippingTableRateQuery($this->resourceConnectionMock);

        $adapterMock = $this->createMock(AdapterInterface::class);
        $this->resourceConnectionMock->method('getConnection')->willReturn($adapterMock);
        $this->resourceConnectionMock->method('getTableName')->willReturnArgument(0);

        $this->selectMock = $this->createMock(Select::class);
        $adapterMock->method('select')->willReturn($this->selectMock);

        $this->selectMock->expects($this->once())
            ->method('from')
            ->with(['tablerate' => 'shipping_tablerate'])
            ->willReturnSelf();
        $this->selectMock->expects($this->once())
            ->method('joinLeft')
            ->with(
                ['region' => 'directory_country_region'],
                'region.region_id = tablerate.dest_region_id',
                ['region_code' => 'region.code', 'region_country_id' => 'region.country_id']
            )
            ->willReturnSelf();
        $this->selectMock->method('where')->willReturnCallback(function ($condition, $value) {
            $this->whereConditions[] = [$condition, $value];

            return $this->selectMock;
        });
    }

    public function testGetFiltersByConditionWebsiteAndCountriesIncludingAnyCountry(): void
    {
        $this->sut->get(['CH', 'DE'], 'package_value', 1);

        $this->assertSame(
            [
                ['tablerate.condition_name = ?', 'package_value'],
                ['tablerate.website_id = ?', 1],
                ['tablerate.dest_country_id in (?)', ['CH', 'DE', '0']],
            ],
            $this->whereConditions
        );
    }

    public function testGetWithoutWebsiteAndCountriesFiltersByConditionOnly(): void
    {
        $this->sut->get([], 'package_weight');

        $this->assertSame([['tablerate.condition_name = ?', 'package_weight']], $this->whereConditions);
    }
}
