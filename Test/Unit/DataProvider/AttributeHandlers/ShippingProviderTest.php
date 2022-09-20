<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\ConfigProvider\AllowedCountriesProvider;
use RunAsRoot\Feed\ConfigProvider\TableRateConditionProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\ShippingProvider;
use RunAsRoot\Feed\DataProvider\CurrencyAmountProvider;
use RunAsRoot\Feed\Query\ShippingTableRateQuery;

final class ShippingProviderTest extends TestCase
{
    private ShippingProvider $sut;

    /** @var AllowedCountriesProvider|MockObject */
    private $allowedCountriesProviderMock;

    /** @var TableRateConditionProvider|MockObject */
    private $tableRateConditionProviderMock;

    /** @var ShippingTableRateQuery|MockObject */
    private $shippingTableRateQueryMock;

    /** @var CurrencyAmountProvider|MockObject */
    private $currencyAmountProviderMock;

    /** @var ResourceConnection|MockObject */
    private $resourceConnectionMock;

    protected function setUp(): void
    {
        $this->allowedCountriesProviderMock = $this->createMock(AllowedCountriesProvider::class);
        $this->tableRateConditionProviderMock = $this->createMock(TableRateConditionProvider::class);
        $this->shippingTableRateQueryMock = $this->createMock(ShippingTableRateQuery::class);
        $this->currencyAmountProviderMock = $this->createMock(CurrencyAmountProvider::class);
        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);

        $this->sut = new ShippingProvider(
            $this->allowedCountriesProviderMock,
            $this->tableRateConditionProviderMock,
            $this->shippingTableRateQueryMock,
            $this->currencyAmountProviderMock,
            $this->resourceConnectionMock
        );
    }

    public function testGet(): void
    {
        $storeId = '1';
        $productMock = $this->createMock(Product::class);

        $productMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);

        $countries = ['CH', 'DE'];
        $this->allowedCountriesProviderMock
            ->expects($this->once())
            ->method('get')
            ->with((int)$storeId)
            ->willReturn($countries);

        $conditionName = 'package_value';
        $this->tableRateConditionProviderMock
            ->expects($this->once())
            ->method('get')
            ->with((int)$storeId)
            ->willReturn($conditionName);

        $selectMock = $this->createMock(Select::class);
        $this->shippingTableRateQueryMock
            ->expects($this->once())
            ->method('get')
            ->with($countries, $conditionName)
            ->willReturn($selectMock);

        $adapterMock = $this->createMock(AdapterInterface::class);
        $this->resourceConnectionMock
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($adapterMock);

        $shippingTableRates = [
            ['dest_country_id' => 'CH', 'price' => '23.68'],
            ['dest_country_id' => 'DE', 'price' => '4.95'],
        ];

        $adapterMock->expects($this->once())
            ->method('fetchAll')
            ->with($selectMock)
            ->willReturn($shippingTableRates);

        $this->currencyAmountProviderMock
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [23.68, $storeId],
                [4.95, $storeId]
            )
            ->willReturnOnConsecutiveCalls('23,68 EUR', '4,95 EUR');

        $expected = [
            ['country' => 'CH', 'price' => '23,68 EUR'],
            ['country' => 'DE', 'price' => '4,95 EUR'],
        ];

        $this->assertEquals($expected, $this->sut->get($productMock));
    }
}
