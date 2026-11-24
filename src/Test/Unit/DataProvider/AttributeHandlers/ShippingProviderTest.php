<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\AllowedCountriesProvider;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\TableRateCarrierConfigProvider;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\TableRateConditionProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\ShippingProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\CurrencyAmountProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\MatchingTableRatesProvider;
use RunAsRoot\GoogleShoppingFeed\Query\ShippingTableRateQuery;

final class ShippingProviderTest extends TestCase
{
    private const STORE_ID = 1;
    private const WEBSITE_ID = 3;
    private const COUNTRIES = ['CH', 'US'];

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

    /** @var TableRateCarrierConfigProvider|MockObject */
    private $tableRateCarrierConfigProviderMock;

    /** @var MatchingTableRatesProvider|MockObject */
    private $matchingTableRatesProviderMock;

    /** @var StoreManagerInterface|MockObject */
    private $storeManagerMock;

    /** @var AdapterInterface|MockObject */
    private $adapterMock;

    protected function setUp(): void
    {
        $this->allowedCountriesProviderMock = $this->createMock(AllowedCountriesProvider::class);
        $this->tableRateConditionProviderMock = $this->createMock(TableRateConditionProvider::class);
        $this->shippingTableRateQueryMock = $this->createMock(ShippingTableRateQuery::class);
        $this->currencyAmountProviderMock = $this->createMock(CurrencyAmountProvider::class);
        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $this->tableRateCarrierConfigProviderMock = $this->createMock(TableRateCarrierConfigProvider::class);
        $this->matchingTableRatesProviderMock = $this->createMock(MatchingTableRatesProvider::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->adapterMock = $this->createMock(AdapterInterface::class);

        $this->resourceConnectionMock->method('getConnection')->willReturn($this->adapterMock);
        $this->allowedCountriesProviderMock->method('get')->with(self::STORE_ID)->willReturn(self::COUNTRIES);
        $this->currencyAmountProviderMock->method('get')->willReturnCallback(
            static fn (float $amount, int $storeId): string => sprintf('%.2f USD@%d', $amount, $storeId)
        );

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getWebsiteId')->willReturn((string)self::WEBSITE_ID);
        $this->storeManagerMock->method('getStore')->with(self::STORE_ID)->willReturn($storeMock);

        $this->sut = new ShippingProvider(
            $this->allowedCountriesProviderMock,
            $this->tableRateConditionProviderMock,
            $this->shippingTableRateQueryMock,
            $this->currencyAmountProviderMock,
            $this->resourceConnectionMock,
            $this->tableRateCarrierConfigProviderMock,
            $this->matchingTableRatesProviderMock,
            $this->storeManagerMock
        );
    }

    public function testGetReturnsMatchingRatesWithRegionAndHandlingFee(): void
    {
        $this->givenCarrier(true, 'package_value_with_discount', 'F', 2.0);
        $tableRates = $this->givenTableRatesAreLoadedOnce();

        $this->matchingTableRatesProviderMock->expects($this->once())
            ->method('get')
            ->with($tableRates, self::COUNTRIES, 29.0)
            ->willReturn([
                ['country' => 'US', 'region' => null, 'price' => 15.0],
                ['country' => 'US', 'region' => 'AK', 'price' => 20.0],
            ]);

        $this->assertSame(
            [
                ['country' => 'US', 'price' => '17.00 USD@1'],
                ['country' => 'US', 'region' => 'AK', 'price' => '22.00 USD@1'],
            ],
            $this->sut->get($this->createProduct(29.0, 1.5))
        );
    }

    public function testGetAppliesPercentHandlingFee(): void
    {
        $this->givenCarrier(true, 'package_value', 'P', 10.0);
        $this->givenTableRatesAreLoadedOnce();
        $this->matchingTableRatesProviderMock->method('get')
            ->willReturn([['country' => 'CH', 'region' => null, 'price' => 20.0]]);

        $this->assertSame(
            [['country' => 'CH', 'price' => '22.00 USD@1']],
            $this->sut->get($this->createProduct(29.0, 1.5))
        );
    }

    /**
     * @dataProvider conditionValueProvider
     */
    public function testGetUsesConditionValueOfSingleItemCart(string $conditionName, float $expectedValue): void
    {
        $this->givenCarrier(true, $conditionName, 'F', 0.0);
        $tableRates = $this->givenTableRatesAreLoadedOnce();

        $this->matchingTableRatesProviderMock->expects($this->once())
            ->method('get')
            ->with($tableRates, self::COUNTRIES, $expectedValue)
            ->willReturn([]);

        $this->assertSame([], $this->sut->get($this->createProduct(29.0, 1.5)));
    }

    /**
     * @return array<string, array{string, float}>
     */
    public static function conditionValueProvider(): array
    {
        return [
            'package value' => ['package_value', 29.0],
            'package value with discount' => ['package_value_with_discount', 29.0],
            'package weight' => ['package_weight', 1.5],
            'package qty' => ['package_qty', 1.0],
        ];
    }

    public function testGetLoadsTableRatesOncePerStore(): void
    {
        $this->givenCarrier(true, 'package_weight', 'F', 0.0);
        $this->givenTableRatesAreLoadedOnce();
        $this->matchingTableRatesProviderMock->expects($this->exactly(2))->method('get')->willReturn([]);

        $this->sut->get($this->createProduct(29.0, 1.5));
        $this->sut->get($this->createProduct(99.0, 3.0));
    }

    public function testGetReturnsNothingWhenCarrierIsInactive(): void
    {
        $this->givenCarrier(false, 'package_weight', 'F', 0.0);
        $this->shippingTableRateQueryMock->expects($this->never())->method('get');
        $this->matchingTableRatesProviderMock->expects($this->never())->method('get');

        $this->assertSame([], $this->sut->get($this->createProduct(29.0, 1.5)));
    }

    public function testGetReturnsNothingForUnknownCondition(): void
    {
        $this->givenCarrier(true, 'unknown_condition', 'F', 0.0);
        $this->shippingTableRateQueryMock->expects($this->never())->method('get');

        $this->assertSame([], $this->sut->get($this->createProduct(29.0, 1.5)));
    }

    private function givenCarrier(bool $isActive, string $conditionName, string $handlingType, float $fee): void
    {
        $this->tableRateCarrierConfigProviderMock->method('isActive')->with(self::STORE_ID)->willReturn($isActive);
        $this->tableRateCarrierConfigProviderMock->method('getHandlingType')->willReturn($handlingType);
        $this->tableRateCarrierConfigProviderMock->method('getHandlingFee')->willReturn($fee);
        $this->tableRateConditionProviderMock->method('get')->with(self::STORE_ID)->willReturn($conditionName);
    }

    /**
     * @return array<array<string, string>>
     */
    private function givenTableRatesAreLoadedOnce(): array
    {
        $selectMock = $this->createMock(Select::class);
        $this->shippingTableRateQueryMock->expects($this->once())
            ->method('get')
            ->with(self::COUNTRIES, $this->anything(), self::WEBSITE_ID)
            ->willReturn($selectMock);

        $tableRates = [['dest_country_id' => 'US', 'price' => '15.0000']];
        $this->adapterMock->expects($this->once())
            ->method('fetchAll')
            ->with($selectMock)
            ->willReturn($tableRates);

        return $tableRates;
    }

    /**
     * @return Product|MockObject
     */
    private function createProduct(float $finalPrice, float $weight)
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getStoreId')->willReturn((string)self::STORE_ID);
        $productMock->method('getFinalPrice')->willReturn($finalPrice);
        $productMock->method('getWeight')->willReturn($weight);

        return $productMock;
    }
}
