<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider;

use RunAsRoot\GoogleShoppingFeed\Query\ShippingTableRateQuery;

use function array_column;
use function array_diff;
use function array_filter;
use function array_unique;
use function array_values;
use function asort;
use function in_array;
use function round;
use function strcmp;
use function usort;

/**
 * Picks the table rate that Magento's table rate carrier charges for a cart holding only the given product,
 * once per destination country and once per region that has its own rate.
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
     * @return array<array{country: string, region: ?string, price: float}>
     */
    public function get(array $tableRates, array $countries, float $conditionValue): array
    {
        $applicableRates = $this->filterApplicableRates($tableRates, $conditionValue);
        $result = [];

        foreach ($this->getDestinationCountries($applicableRates, $countries) as $countryId) {
            $countryRate = $this->findRate($applicableRates, $countryId, self::NO_REGION_ID);

            if ($this->isChargeable($countryRate)) {
                $result[] = $this->createMatchingRate($countryId, null, $countryRate);
            }

            foreach ($this->getRegionCodes($applicableRates, $countryId) as $regionId => $regionCode) {
                $regionRate = $this->findRate($applicableRates, $countryId, $regionId);

                if (!$this->isChargeable($regionRate) || (int)$regionRate['dest_region_id'] !== $regionId) {
                    continue;
                }

                $result[] = $this->createMatchingRate($countryId, $regionCode, $regionRate);
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $tableRate
     * @return array{country: string, region: ?string, price: float}
     */
    private function createMatchingRate(string $countryId, ?string $regionCode, array $tableRate): array
    {
        return [ 'country' => $countryId, 'region' => $regionCode, 'price' => (float)$tableRate['price'] ];
    }

    /**
     * @param array<array<string, mixed>> $tableRates
     * @return array<array<string, mixed>>
     */
    private function filterApplicableRates(array $tableRates, float $conditionValue): array
    {
        $roundedConditionValue = round($conditionValue, 4);

        return array_values(array_filter(
            $tableRates,
            static function (array $tableRate) use ($roundedConditionValue): bool {
                if ((float)$tableRate['condition_value'] > $roundedConditionValue) {
                    return false;
                }

                if ($tableRate['dest_zip'] === self::ANY_ZIP) {
                    return true;
                }

                return $tableRate['dest_zip'] === self::EMPTY_ZIP
                    && $tableRate['dest_country_id'] !== ShippingTableRateQuery::ANY_COUNTRY_ID;
            }
        ));
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
                || !$this->isIsoRegionCode($countryId, (string)$tableRate['region_code'])
            ) {
                continue;
            }

            $regionCodes[$regionId] = (string)$tableRate['region_code'];
        }

        asort($regionCodes);

        return $regionCodes;
    }

    /**
     * Mirrors the destination matching and ordering of Magento's table rate carrier.
     *
     * @see \Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\RateQuery::prepareSelect()
     * @param array<array<string, mixed>> $tableRates
     * @return array<string, mixed>|null
     */
    private function findRate(array $tableRates, string $countryId, int $regionId): ?array
    {
        $candidates = array_values(array_filter(
            $tableRates,
            function (array $tableRate) use ($countryId, $regionId): bool {
                $rateRegionId = (int)$tableRate['dest_region_id'];

                return $this->isAppliedToCountry($tableRate, $countryId)
                    && ($rateRegionId === self::NO_REGION_ID || $rateRegionId === $regionId);
            }
        ));

        usort($candidates, static function (array $left, array $right): int {
            return strcmp((string)$right['dest_country_id'], (string)$left['dest_country_id'])
                ?: (int)$right['dest_region_id'] <=> (int)$left['dest_region_id']
                ?: strcmp((string)$right['dest_zip'], (string)$left['dest_zip'])
                ?: (float)$right['condition_value'] <=> (float)$left['condition_value'];
        });

        return $candidates[0] ?? null;
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
