<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider;

use RunAsRoot\GoogleShoppingFeed\Query\ShippingTableRateQuery;

use function array_column;
use function array_diff;
use function array_filter;
use function array_merge;
use function array_push;
use function array_unique;
use function array_values;
use function asort;
use function in_array;
use function max;
use function round;
use function strcmp;
use function usort;

/**
 * Picks the table rate that Magento's table rate carrier charges for a cart holding only the given product,
 * once per destination country and once per region that has its own rate.
 *
 * @phpstan-type RateDestination array{
 *     country: string,
 *     region_id: int,
 *     region: ?string,
 *     rates: array<array<string, mixed>>,
 *     unexported_region_rates: array<int, array<array<string, mixed>>>
 * }
 * @phpstan-type MatchingRate array{country: string, region: ?string, price: float}
 */
class MatchingTableRatesProvider
{
    private const ANY_ZIP = '*';
    private const EMPTY_ZIP = '';
    private const NO_REGION_ID = 0;

    /**
     * Countries whose Magento region codes are ISO 3166-2 subdivision codes without the country prefix.
     */
    private const ISO_REGION_CODE_COUNTRIES = [ 'AU', 'BE', 'BR', 'CA', 'CH', 'MX', 'RO', 'US' ];

    /**
     * Magento regions of the countries above that are not ISO 3166-2 subdivisions of that country.
     */
    private const NON_ISO_REGION_CODES = [
        'US' => ['AA', 'AE', 'AP', 'FM', 'MH', 'PW'],
    ];

    /**
     * @param array<array<string, mixed>> $tableRates rows selected by ShippingTableRateQuery
     * @param string[] $countries
     * @return array<MatchingRate>
     */
    public function get(array $tableRates, array $countries, float $conditionValue): array
    {
        return $this->getFromRateIndex($this->createRateIndex($tableRates, $countries), $conditionValue);
    }

    /**
     * Groups the rates per destination in the order Magento's table rate carrier prefers them,
     * so that matching a product only needs the first rate within its condition value.
     * Regions that can't be exported keep their rates on the country, which then charges the highest of them.
     *
     * @see \Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\RateQuery::prepareSelect()
     * @param array<array<string, mixed>> $tableRates rows selected by ShippingTableRateQuery
     * @param string[] $countries
     * @return array<RateDestination>
     */
    public function createRateIndex(array $tableRates, array $countries): array
    {
        $anyZipRates = $this->filterAnyZipRates($tableRates);
        $rateIndex = [];

        foreach ($this->getDestinationCountries($anyZipRates, $countries) as $countryId) {
            $countryDestination = $this->createDestination($anyZipRates, $countryId, self::NO_REGION_ID, null);
            $regionDestinations = [];

            foreach ($this->getRegionCodes($anyZipRates, $countryId) as $regionId => $regionCode) {
                $regionDestination = $this->createDestination($anyZipRates, $countryId, $regionId, $regionCode);

                if (!$this->isIsoRegionCode($countryId, $regionCode)) {
                    $countryDestination['unexported_region_rates'][$regionId] = $regionDestination['rates'];
                    continue;
                }

                $regionDestinations[] = $regionDestination;
            }

            $rateIndex[] = $countryDestination;
            array_push($rateIndex, ...$regionDestinations);
        }

        return $rateIndex;
    }

    /**
     * Rates of explicitly listed countries come first, followed by rates applied from rows for any country.
     *
     * @param array<RateDestination> $rateIndex
     * @return array<MatchingRate>
     */
    public function getFromRateIndex(array $rateIndex, float $conditionValue): array
    {
        $groupedRates = $this->getGroupedFromRateIndex($rateIndex, $conditionValue);

        return array_merge($groupedRates['country_rates'], $groupedRates['any_country_rates']);
    }

    /**
     * @param array<RateDestination> $rateIndex
     * @return array{country_rates: array<MatchingRate>, any_country_rates: array<MatchingRate>}
     */
    public function getGroupedFromRateIndex(array $rateIndex, float $conditionValue): array
    {
        $roundedConditionValue = round($conditionValue, 4);
        $groupedRates = [ 'country_rates' => [], 'any_country_rates' => [] ];

        foreach ($rateIndex as $destination) {
            $rate = $this->findDestinationRate(
                $destination['rates'],
                $destination['region_id'],
                $roundedConditionValue
            );

            if ($rate === null) {
                continue;
            }

            $matchingRate = [
                'country' => $destination['country'],
                'region' => $destination['region'],
                'price' => $this->getHighestPrice(
                    $rate,
                    $destination['unexported_region_rates'],
                    $roundedConditionValue
                ),
            ];
            $group = $rate['dest_country_id'] === ShippingTableRateQuery::ANY_COUNTRY_ID
                ? 'any_country_rates'
                : 'country_rates';
            $groupedRates[$group][] = $matchingRate;
        }

        return $groupedRates;
    }

    /**
     * @param array<array<string, mixed>> $sortedRates
     * @return array<string, mixed>|null
     */
    private function findDestinationRate(array $sortedRates, int $regionId, float $conditionValue): ?array
    {
        $rate = $this->findFirstRateWithin($sortedRates, $conditionValue);

        if (!$this->isChargeable($rate) || (int)$rate['dest_region_id'] !== $regionId) {
            return null;
        }

        return $rate;
    }

    /**
     * @param array<string, mixed> $rate
     * @param array<int, array<array<string, mixed>>> $unexportedRegionRates
     */
    private function getHighestPrice(array $rate, array $unexportedRegionRates, float $conditionValue): float
    {
        $price = (float)$rate['price'];

        foreach ($unexportedRegionRates as $regionId => $regionRates) {
            $regionRate = $this->findDestinationRate($regionRates, $regionId, $conditionValue);

            if ($regionRate === null) {
                continue;
            }

            $price = max($price, (float)$regionRate['price']);
        }

        return $price;
    }

    /**
     * @param array<array<string, mixed>> $tableRates
     * @return array<array<string, mixed>>
     */
    private function filterAnyZipRates(array $tableRates): array
    {
        return array_values(array_filter(
            $tableRates,
            static function (array $tableRate): bool {
                if ($tableRate['dest_zip'] === self::ANY_ZIP) {
                    return true;
                }

                return $tableRate['dest_zip'] === self::EMPTY_ZIP
                    && $tableRate['dest_country_id'] !== ShippingTableRateQuery::ANY_COUNTRY_ID;
            }
        ));
    }

    /**
     * @param array<array<string, mixed>> $sortedRates
     * @return array<string, mixed>|null
     */
    private function findFirstRateWithin(array $sortedRates, float $conditionValue): ?array
    {
        foreach ($sortedRates as $rate) {
            if ((float)$rate['condition_value'] <= $conditionValue) {
                return $rate;
            }
        }

        return null;
    }

    /**
     * @param array<array<string, mixed>> $tableRates
     * @param string[] $countries
     * @return string[]
     */
    private function getDestinationCountries(array $tableRates, array $countries): array
    {
        if (!empty($countries)) {
            return array_values(array_unique($countries));
        }

        $rateCountries = array_unique(array_column($tableRates, 'dest_country_id'));

        return array_values(array_diff($rateCountries, [ ShippingTableRateQuery::ANY_COUNTRY_ID ]));
    }

    /**
     * @param array<array<string, mixed>> $tableRates
     * @return array<int, string>
     */
    private function getRegionCodes(array $tableRates, string $countryId): array
    {
        $regionCodes = [];

        foreach ($tableRates as $tableRate) {
            $regionId = (int)$tableRate['dest_region_id'];

            if (
                $regionId === self::NO_REGION_ID
                || ($tableRate['region_country_id'] ?? null) !== $countryId
                || !$this->isAppliedToCountry($tableRate, $countryId)
            ) {
                continue;
            }

            $regionCodes[$regionId] = (string)$tableRate['region_code'];
        }

        asort($regionCodes);

        return $regionCodes;
    }

    /**
     * @param array<array<string, mixed>> $tableRates
     * @return RateDestination
     */
    private function createDestination(array $tableRates, string $countryId, int $regionId, ?string $regionCode): array
    {
        $rates = array_values(array_filter(
            $tableRates,
            function (array $tableRate) use ($countryId, $regionId): bool {
                $rateRegionId = (int)$tableRate['dest_region_id'];

                return $this->isAppliedToCountry($tableRate, $countryId)
                    && ($rateRegionId === self::NO_REGION_ID || $rateRegionId === $regionId);
            }
        ));

        usort($rates, static function (array $left, array $right): int {
            return strcmp((string)$right['dest_country_id'], (string)$left['dest_country_id'])
                ?: (int)$right['dest_region_id'] <=> (int)$left['dest_region_id']
                ?: strcmp((string)$right['dest_zip'], (string)$left['dest_zip'])
                ?: (float)$right['condition_value'] <=> (float)$left['condition_value'];
        });

        return [
            'country' => $countryId,
            'region_id' => $regionId,
            'region' => $regionCode,
            'rates' => $rates,
            'unexported_region_rates' => [],
        ];
    }

    /**
     * @param array<string, mixed>|null $tableRate
     * @phpstan-assert-if-true array<string, mixed> $tableRate
     */
    private function isChargeable(?array $tableRate): bool
    {
        return $tableRate !== null && (float)$tableRate['price'] >= 0;
    }

    /**
     * @param array<string, mixed> $tableRate
     */
    private function isAppliedToCountry(array $tableRate, string $countryId): bool
    {
        return $tableRate['dest_country_id'] === $countryId
            || $tableRate['dest_country_id'] === ShippingTableRateQuery::ANY_COUNTRY_ID;
    }

    private function isIsoRegionCode(string $countryId, string $regionCode): bool
    {
        return in_array($countryId, self::ISO_REGION_CODE_COUNTRIES, true)
            && !in_array($regionCode, self::NON_ISO_REGION_CODES[$countryId] ?? [], true);
    }
}
