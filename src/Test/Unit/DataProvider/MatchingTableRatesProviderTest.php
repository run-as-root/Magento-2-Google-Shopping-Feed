<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider;

use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\DataProvider\MatchingTableRatesProvider;

final class MatchingTableRatesProviderTest extends TestCase
{
    private MatchingTableRatesProvider $sut;

    protected function setUp(): void
    {
        $this->sut = new MatchingTableRatesProvider();
    }

    /**
     * @dataProvider lumaConditionValueProvider
     */
    public function testGetPicksHighestConditionValueNotAboveProductValuePerRegion(
        float $conditionValue,
        float $countryPrice,
        float $regionPrice
    ): void {
        $expected = [
            ['country' => 'US', 'region' => null, 'price' => $countryPrice],
            ['country' => 'US', 'region' => 'AK', 'price' => $regionPrice],
            ['country' => 'US', 'region' => 'HI', 'price' => $regionPrice],
        ];

        $this->assertSame($expected, $this->sut->get($this->getLumaTableRates(), ['US'], $conditionValue));
    }

    /**
     * @return array<string, array{float, float, float}>
     */
    public static function lumaConditionValueProvider(): array
    {
        return [
            'cheap product' => [5.0, 15.0, 20.0],
            'just below tier' => [49.99, 15.0, 20.0],
            'exactly at tier' => [50.0, 10.0, 15.0],
            'expensive product' => [99.0, 10.0, 15.0],
            'above last tier' => [150.0, 5.0, 10.0],
        ];
    }

    public function testGetReturnsNothingWhenNoRateMatchesConditionValue(): void
    {
        $tableRates = [$this->createTableRate('US', 0, '*', 10.0, 7.0)];

        $this->assertSame([], $this->sut->get($tableRates, ['US'], 5.0));
    }

    public function testGetSkipsRegionWhenOnlyCountryRateMatchesConditionValue(): void
    {
        $tableRates = [
            $this->createTableRate('US', 0, '*', 0.0, 15.0),
            $this->createTableRate('US', 2, '*', 50.0, 30.0, 'AK', 'US'),
        ];

        $this->assertSame(
            [['country' => 'US', 'region' => null, 'price' => 15.0]],
            $this->sut->get($tableRates, ['US'], 10.0)
        );
    }

    public function testGetSkipsZipSpecificRates(): void
    {
        $tableRates = [
            $this->createTableRate('US', 0, '*', 0.0, 15.0),
            $this->createTableRate('US', 0, '90210', 0.0, 1.0),
            $this->createTableRate('US', 12, '90*', 0.0, 2.0, 'CA', 'US'),
        ];

        $this->assertSame(
            [['country' => 'US', 'region' => null, 'price' => 15.0]],
            $this->sut->get($tableRates, ['US'], 10.0)
        );
    }

    public function testGetTreatsEmptyZipAsAnyZipButPrefersAsterisk(): void
    {
        $tableRates = [
            $this->createTableRate('US', 0, '', 0.0, 12.0),
            $this->createTableRate('US', 0, '*', 0.0, 15.0),
        ];

        $this->assertSame(
            [['country' => 'US', 'region' => null, 'price' => 15.0]],
            $this->sut->get($tableRates, ['US'], 10.0)
        );
    }

    public function testGetChargesHighestRateOfNonIsoRegionsOnTheCountry(): void
    {
        $tableRates = [
            $this->createTableRate('DE', 0, '*', 0.0, 6.0),
            $this->createTableRate('DE', 81, '*', 0.0, 11.0, 'BAY', 'DE'),
            $this->createTableRate('DE', 82, '*', 0.0, 3.0, 'BER', 'DE'),
            $this->createTableRate('US', 0, '*', 0.0, 15.0),
            $this->createTableRate('US', 9, '*', 0.0, 25.0, 'AE', 'US'),
            $this->createTableRate('US', 2, '*', 0.0, 20.0, 'AK', 'US'),
        ];

        $this->assertSame(
            [
                ['country' => 'DE', 'region' => null, 'price' => 11.0],
                ['country' => 'US', 'region' => null, 'price' => 25.0],
                ['country' => 'US', 'region' => 'AK', 'price' => 20.0],
            ],
            $this->sut->get($tableRates, ['DE', 'US'], 10.0)
        );
    }

    public function testGetIgnoresNonIsoRegionsWithoutOwnRateWithinConditionValue(): void
    {
        $tableRates = [
            $this->createTableRate('DE', 0, '*', 0.0, 6.0),
            $this->createTableRate('DE', 81, '*', 50.0, 11.0, 'BAY', 'DE'),
            $this->createTableRate('DE', 82, '*', 0.0, -1.0, 'BER', 'DE'),
        ];

        $this->assertSame(
            [['country' => 'DE', 'region' => null, 'price' => 6.0]],
            $this->sut->get($tableRates, ['DE'], 10.0)
        );
    }

    public function testGetSkipsCountryWithoutCountryRateEvenIfNonIsoRegionsHaveRates(): void
    {
        $tableRates = [
            $this->createTableRate('DE', 81, '*', 0.0, 11.0, 'BAY', 'DE'),
        ];

        $this->assertSame([], $this->sut->get($tableRates, ['DE'], 10.0));
    }

    public function testGetGroupedFromRateIndexSeparatesAnyCountryRates(): void
    {
        $tableRates = [
            $this->createTableRate('0', 0, '*', 0.0, 30.0),
            $this->createTableRate('DE', 0, '*', 0.0, 4.95),
        ];
        $rateIndex = $this->sut->createRateIndex($tableRates, ['US', 'DE']);

        $this->assertSame(
            [
                'country_rates' => [['country' => 'DE', 'region' => null, 'price' => 4.95]],
                'any_country_rates' => [['country' => 'US', 'region' => null, 'price' => 30.0]],
            ],
            $this->sut->getGroupedFromRateIndex($rateIndex, 10.0)
        );
    }

    public function testGetAppliesAnyCountryRateToEachAllowedCountryWithLowerPrecedence(): void
    {
        $tableRates = [
            $this->createTableRate('0', 0, '*', 0.0, 30.0),
            $this->createTableRate('0', 0, '', 0.0, 99.0),
            $this->createTableRate('0', 2, '*', 0.0, 40.0, 'AK', 'US'),
            $this->createTableRate('DE', 0, '*', 0.0, 4.95),
        ];

        $this->assertSame(
            [
                ['country' => 'DE', 'region' => null, 'price' => 4.95],
                ['country' => 'US', 'region' => null, 'price' => 30.0],
                ['country' => 'US', 'region' => 'AK', 'price' => 40.0],
            ],
            $this->sut->get($tableRates, ['DE', 'US'], 10.0)
        );
    }

    public function testGetListsCountryRatesBeforeAnyCountryRates(): void
    {
        $tableRates = [
            $this->createTableRate('0', 0, '*', 0.0, 30.0),
            $this->createTableRate('0', 2, '*', 0.0, 40.0, 'AK', 'US'),
            $this->createTableRate('FR', 0, '*', 0.0, 8.0),
            $this->createTableRate('DE', 0, '*', 0.0, 4.95),
        ];

        $this->assertSame(
            [
                ['country' => 'FR', 'region' => null, 'price' => 8.0],
                ['country' => 'DE', 'region' => null, 'price' => 4.95],
                ['country' => 'US', 'region' => null, 'price' => 30.0],
                ['country' => 'US', 'region' => 'AK', 'price' => 40.0],
                ['country' => 'CH', 'region' => null, 'price' => 30.0],
            ],
            $this->sut->get($tableRates, ['US', 'FR', 'CH', 'DE'], 10.0)
        );
    }

    public function testGetFromRateIndexMatchesEachConditionValueAgainstTheSameIndex(): void
    {
        $rateIndex = $this->sut->createRateIndex($this->getLumaTableRates(), ['US']);

        $this->assertSame(
            ['country' => 'US', 'region' => null, 'price' => 15.0],
            $this->sut->getFromRateIndex($rateIndex, 5.0)[0]
        );
        $this->assertSame(
            ['country' => 'US', 'region' => null, 'price' => 10.0],
            $this->sut->getFromRateIndex($rateIndex, 99.0)[0]
        );
    }

    public function testGetPrefersCountryRateOverAnyCountryRegionRate(): void
    {
        $tableRates = [
            $this->createTableRate('US', 0, '*', 0.0, 15.0),
            $this->createTableRate('0', 2, '*', 0.0, 40.0, 'AK', 'US'),
        ];

        $this->assertSame(
            [['country' => 'US', 'region' => null, 'price' => 15.0]],
            $this->sut->get($tableRates, ['US'], 10.0)
        );
    }

    public function testGetSkipsNegativePrice(): void
    {
        $tableRates = [
            $this->createTableRate('US', 0, '*', 0.0, 15.0),
            $this->createTableRate('US', 0, '*', 50.0, -1.0),
        ];

        $this->assertSame([], $this->sut->get($tableRates, ['US'], 60.0));
    }

    public function testGetUsesRateCountriesWhenNoCountriesAreAllowed(): void
    {
        $tableRates = [
            $this->createTableRate('0', 0, '*', 0.0, 30.0),
            $this->createTableRate('CH', 0, '*', 0.0, 23.68),
        ];

        $this->assertSame(
            [['country' => 'CH', 'region' => null, 'price' => 23.68]],
            $this->sut->get($tableRates, [], 10.0)
        );
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function getLumaTableRates(): array
    {
        $tableRates = [];

        foreach ([[0, null], [2, 'AK'], [21, 'HI']] as [$regionId, $regionCode]) {
            $regionSurcharge = $regionId === 0 ? 0.0 : 5.0;
            foreach ([0.0 => 15.0, 50.0 => 10.0, 100.0 => 5.0] as $conditionValue => $price) {
                $tableRates[] = $this->createTableRate(
                    'US',
                    $regionId,
                    '*',
                    (float)$conditionValue,
                    $price + $regionSurcharge,
                    $regionCode,
                    $regionCode === null ? null : 'US'
                );
            }
        }

        return $tableRates;
    }

    /**
     * @return array<string, mixed>
     */
    private function createTableRate(
        string $countryId,
        int $regionId,
        string $zip,
        float $conditionValue,
        float $price,
        ?string $regionCode = null,
        ?string $regionCountryId = null
    ): array {
        return [
            'website_id' => '1',
            'dest_country_id' => $countryId,
            'dest_region_id' => (string)$regionId,
            'dest_zip' => $zip,
            'condition_name' => 'package_value_with_discount',
            'condition_value' => number_format($conditionValue, 4, '.', ''),
            'price' => number_format($price, 4, '.', ''),
            'cost' => '0.0000',
            'region_code' => $regionCode,
            'region_country_id' => $regionCountryId,
        ];
    }
}
