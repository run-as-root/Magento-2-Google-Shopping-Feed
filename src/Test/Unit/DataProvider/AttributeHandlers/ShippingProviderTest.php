<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
    private const OTHER_STORE_ID = 2;
    private const OTHER_WEBSITE_ID = 4;
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

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

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
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->adapterMock = $this->createMock(AdapterInterface::class);

        $this->resourceConnectionMock->method('getConnection')->willReturn($this->adapterMock);
        $this->allowedCountriesProviderMock->method('get')->willReturn(self::COUNTRIES);
        $this->currencyAmountProviderMock->method('get')->willReturnCallback(
            static fn (float $amount, int $storeId): string => sprintf('%.2f USD@%d', $amount, $storeId)
        );

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getWebsiteId')->willReturn((string)self::WEBSITE_ID);
        $otherStoreMock = $this->createMock(StoreInterface::class);
        $otherStoreMock->method('getWebsiteId')->willReturn((string)self::OTHER_WEBSITE_ID);
        $this->storeManagerMock->method('getStore')->willReturnMap([
            [ self::STORE_ID, $storeMock ],
            [ self::OTHER_STORE_ID, $otherStoreMock ],
        ]);

        $this->sut = new ShippingProvider(
            $this->allowedCountriesProviderMock,
            $this->tableRateConditionProviderMock,
            $this->shippingTableRateQueryMock,
            $this->currencyAmountProviderMock,
            $this->resourceConnectionMock,
            $this->tableRateCarrierConfigProviderMock,
            $this->matchingTableRatesProviderMock,
            $this->storeManagerMock,
            $this->loggerMock
        );
    }

    public function testGetReturnsMatchingRatesWithRegionAndHandlingFee(): void
    {
        $this->givenCarrier(true, 'package_value_with_discount', 'F', 2.0);
        $rateIndex = $this->givenRateIndexIsCreatedOnce(self::COUNTRIES);

        $this->matchingTableRatesProviderMock->expects($this->once())
            ->method('getFromRateIndex')
            ->with($rateIndex, 29.0)
            ->willReturn([
                [ 'country' => 'US', 'region' => null, 'price' => 15.0 ],
                [ 'country' => 'US', 'region' => 'AK', 'price' => 20.0 ],
            ]);

        $this->assertSame(
            [
                [ 'country' => 'US', 'price' => '17.00 USD@1' ],
                [ 'country' => 'US', 'region' => 'AK', 'price' => '22.00 USD@1' ],
            ],
            $this->sut->get($this->createProduct(29.0, 1.5))
        );
    }

    public function testGetAppliesPercentHandlingFee(): void
    {
        $this->givenCarrier(true, 'package_value', 'P', 10.0);
        $this->givenRateIndexIsCreatedOnce(self::COUNTRIES);
        $this->matchingTableRatesProviderMock->method('getFromRateIndex')
            ->willReturn([ [ 'country' => 'CH', 'region' => null, 'price' => 20.0 ] ]);

        $this->assertSame(
            [ [ 'country' => 'CH', 'price' => '22.00 USD@1' ] ],
            $this->sut->get($this->createProduct(29.0, 1.5))
        );
    }

    /**
     * @dataProvider conditionValueProvider
     */
    public function testGetUsesConditionValueOfSingleItemCart(string $conditionName, float $expectedValue): void
    {
        $this->givenCarrier(true, $conditionName, 'F', 0.0);
        $rateIndex = $this->givenRateIndexIsCreatedOnce(self::COUNTRIES);

        $this->matchingTableRatesProviderMock->expects($this->once())
            ->method('getFromRateIndex')
            ->with($rateIndex, $expectedValue)
            ->willReturn([]);

        $this->assertSame([], $this->sut->get($this->createProduct(29.0, 1.5)));
    }

    /**
     * @return array<string, array{string, float}>
     */
    public static function conditionValueProvider(): array
    {
        return [
            'package value' => [ 'package_value', 29.0 ],
            'package value with discount' => [ 'package_value_with_discount', 29.0 ],
            'package weight' => [ 'package_weight', 1.5 ],
            'package qty' => [ 'package_qty', 1.0 ],
        ];
    }

    public function testGetUsesHeaviestEnabledChildWeightForConfigurableProduct(): void
    {
        $this->givenCarrier(true, 'package_weight', 'F', 0.0);
        $rateIndex = $this->givenRateIndexIsCreatedOnce(self::COUNTRIES);

        $productMock = $this->createProduct(29.0, 0.0);
        $typeInstanceMock = $this->createMock(Configurable::class);
        $productMock->method('getTypeInstance')->willReturn($typeInstanceMock);
        $typeInstanceMock->method('getUsedProducts')->with($productMock)->willReturn([
            $this->createChildProduct(2.0, 1),
            $this->createChildProduct(9.0, 2),
            $this->createChildProduct(4.5, 1),
        ]);

        $this->matchingTableRatesProviderMock->expects($this->once())
            ->method('getFromRateIndex')
            ->with($rateIndex, 4.5)
            ->willReturn([]);

        $this->sut->get($productMock);
    }

    public function testGetCachesRateIndexPerStoreAndScopesItToTheStoreWebsite(): void
    {
        $this->givenCarrier(true, 'package_weight', 'F', 0.0);
        $selectMock = $this->createMock(Select::class);
        $otherSelectMock = $this->createMock(Select::class);
        $this->shippingTableRateQueryMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [ self::COUNTRIES, 'package_weight', self::WEBSITE_ID, $selectMock ],
                [ self::COUNTRIES, 'package_weight', self::OTHER_WEBSITE_ID, $otherSelectMock ],
            ]);
        $tableRates = [ [ 'dest_country_id' => 'US', 'price' => '15.0000' ] ];
        $otherTableRates = [ [ 'dest_country_id' => 'CH', 'price' => '23.6800' ] ];
        $this->adapterMock->expects($this->exactly(2))
            ->method('fetchAll')
            ->willReturnCallback(
                static fn (Select $select): array => $select === $selectMock ? $tableRates : $otherTableRates
            );
        $rateIndex = [ [ 'country' => 'US', 'region_id' => 0, 'region' => null, 'rates' => $tableRates ] ];
        $otherRateIndex = [ [ 'country' => 'CH', 'region_id' => 0, 'region' => null, 'rates' => $otherTableRates ] ];
        $this->matchingTableRatesProviderMock->expects($this->exactly(2))
            ->method('createRateIndex')
            ->willReturnMap([
                [ $tableRates, self::COUNTRIES, $rateIndex ],
                [ $otherTableRates, self::COUNTRIES, $otherRateIndex ],
            ]);
        $usedRateIndexes = [];
        $this->matchingTableRatesProviderMock->expects($this->exactly(4))
            ->method('getFromRateIndex')
            ->willReturnCallback(static function (array $usedRateIndex) use (&$usedRateIndexes): array {
                $usedRateIndexes[] = $usedRateIndex;

                return [];
            });

        $this->sut->get($this->createProduct(29.0, 1.5));
        $this->sut->get($this->createProduct(29.0, 1.5, self::OTHER_STORE_ID));
        $this->sut->get($this->createProduct(99.0, 3.0));
        $this->sut->get($this->createProduct(99.0, 3.0, self::OTHER_STORE_ID));

        $this->assertSame([ $rateIndex, $otherRateIndex, $rateIndex, $otherRateIndex ], $usedRateIndexes);
    }

    public function testGetRestrictsCountriesToCarrierSpecificCountries(): void
    {
        $this->givenCarrier(true, 'package_weight', 'F', 0.0, [ 'US', 'DE' ]);
        $this->givenRateIndexIsCreatedOnce([ 'US' ]);
        $this->matchingTableRatesProviderMock->method('getFromRateIndex')->willReturn([]);

        $this->sut->get($this->createProduct(29.0, 1.5));
    }

    public function testGetUsesCarrierSpecificCountriesWhenNoCountriesAreAllowed(): void
    {
        $this->allowedCountriesProviderMock = $this->createMock(AllowedCountriesProvider::class);
        $this->allowedCountriesProviderMock->method('get')->willReturn([]);
        $this->recreateSut();
        $this->givenCarrier(true, 'package_weight', 'F', 0.0, [ 'DE' ]);
        $this->givenRateIndexIsCreatedOnce([ 'DE' ]);
        $this->matchingTableRatesProviderMock->method('getFromRateIndex')->willReturn([]);

        $this->sut->get($this->createProduct(29.0, 1.5));
    }

    public function testGetReturnsNothingWhenCarrierShipsToNoAllowedCountry(): void
    {
        $this->givenCarrier(true, 'package_weight', 'F', 0.0, [ 'DE' ]);
        $this->shippingTableRateQueryMock->expects($this->never())->method('get');
        $this->matchingTableRatesProviderMock->method('getFromRateIndex')->with([])->willReturn([]);

        $this->assertSame([], $this->sut->get($this->createProduct(29.0, 1.5)));
    }

    public function testGetTruncatesToGoogleLimitAndWarnsOncePerStore(): void
    {
        $this->givenCarrier(true, 'package_weight', 'F', 0.0);
        $this->givenRateIndexIsCreatedOnce(self::COUNTRIES);
        $matchingRates = [];
        for ($index = 0; $index < 150; $index++) {
            $matchingRates[] = [ 'country' => sprintf('C%03d', $index), 'region' => null, 'price' => 1.0 ];
        }
        $this->matchingTableRatesProviderMock->method('getFromRateIndex')->willReturn($matchingRates);

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('100'), [ 'store_id' => self::STORE_ID, 'shipping_entries' => 150 ]);

        $result = $this->sut->get($this->createProduct(29.0, 1.5));
        $this->sut->get($this->createProduct(99.0, 3.0));

        $this->assertCount(100, $result);
        $this->assertSame('C000', $result[0]['country']);
        $this->assertSame('C099', $result[99]['country']);
    }

    public function testGetReturnsNothingForVirtualProduct(): void
    {
        $this->givenCarrier(true, 'package_weight', 'F', 0.0);
        $this->shippingTableRateQueryMock->expects($this->never())->method('get');

        $productMock = $this->createProduct(29.0, 1.5);
        $productMock->method('isVirtual')->willReturn(true);

        $this->assertSame([], $this->sut->get($productMock));
    }

    public function testGetReturnsNothingWhenCarrierIsInactive(): void
    {
        $this->givenCarrier(false, 'package_weight', 'F', 0.0);
        $this->shippingTableRateQueryMock->expects($this->never())->method('get');
        $this->matchingTableRatesProviderMock->expects($this->never())->method('getFromRateIndex');

        $this->assertSame([], $this->sut->get($this->createProduct(29.0, 1.5)));
    }

    public function testGetReturnsNothingForUnknownCondition(): void
    {
        $this->givenCarrier(true, 'unknown_condition', 'F', 0.0);
        $this->shippingTableRateQueryMock->expects($this->never())->method('get');

        $this->assertSame([], $this->sut->get($this->createProduct(29.0, 1.5)));
    }

    /**
     * @param string[]|null $specificCountries
     */
    private function givenCarrier(
        bool $isActive,
        string $conditionName,
        string $handlingType,
        float $fee,
        ?array $specificCountries = null
    ): void {
        $this->tableRateCarrierConfigProviderMock->method('isActive')->willReturn($isActive);
        $this->tableRateCarrierConfigProviderMock->method('getHandlingType')->willReturn($handlingType);
        $this->tableRateCarrierConfigProviderMock->method('getHandlingFee')->willReturn($fee);
        $this->tableRateCarrierConfigProviderMock->method('isSpecificCountriesOnly')
            ->willReturn($specificCountries !== null);
        $this->tableRateCarrierConfigProviderMock->method('getSpecificCountries')
            ->willReturn($specificCountries ?? []);
        $this->tableRateConditionProviderMock->method('get')->willReturn($conditionName);
    }

    /**
     * @param string[] $countries
     * @return array<array<string, mixed>>
     */
    private function givenRateIndexIsCreatedOnce(array $countries): array
    {
        $selectMock = $this->createMock(Select::class);
        $this->shippingTableRateQueryMock->expects($this->once())
            ->method('get')
            ->with($countries, $this->anything(), self::WEBSITE_ID)
            ->willReturn($selectMock);

        $tableRates = [ [ 'dest_country_id' => 'US', 'price' => '15.0000' ] ];
        $this->adapterMock->expects($this->once())
            ->method('fetchAll')
            ->with($selectMock)
            ->willReturn($tableRates);

        $rateIndex = [ [ 'country' => 'US', 'region_id' => 0, 'region' => null, 'rates' => $tableRates ] ];
        $this->matchingTableRatesProviderMock->expects($this->once())
            ->method('createRateIndex')
            ->with($tableRates, $countries)
            ->willReturn($rateIndex);

        return $rateIndex;
    }

    private function recreateSut(): void
    {
        $this->sut = new ShippingProvider(
            $this->allowedCountriesProviderMock,
            $this->tableRateConditionProviderMock,
            $this->shippingTableRateQueryMock,
            $this->currencyAmountProviderMock,
            $this->resourceConnectionMock,
            $this->tableRateCarrierConfigProviderMock,
            $this->matchingTableRatesProviderMock,
            $this->storeManagerMock,
            $this->loggerMock
        );
    }

    /**
     * @return Product|MockObject
     */
    private function createProduct(float $finalPrice, float $weight, int $storeId = self::STORE_ID)
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getStoreId')->willReturn((string)$storeId);
        $productMock->method('getFinalPrice')->willReturn($finalPrice);
        $productMock->method('getWeight')->willReturn($weight);

        return $productMock;
    }

    /**
     * @return Product|MockObject
     */
    private function createChildProduct(float $weight, int $status)
    {
        $childProductMock = $this->createMock(Product::class);
        $childProductMock->method('getWeight')->willReturn($weight);
        $childProductMock->method('getStatus')->willReturn($status);

        return $childProductMock;
    }
}
