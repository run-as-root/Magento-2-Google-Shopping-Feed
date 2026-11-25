<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\AllowedCountriesProvider;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\TableRateCarrierConfigProvider;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\TableRateConditionProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\CurrencyAmountProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\MatchingTableRatesProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\SingleItemGuestCartPriceProvider;
use RunAsRoot\GoogleShoppingFeed\Query\ShippingTableRateQuery;
use function array_intersect;
use function array_merge;
use function array_slice;
use function array_values;
use function count;
use function max;

/**
 * @phpstan-import-type RateDestination from MatchingTableRatesProvider
 * @phpstan-import-type MatchingRate from MatchingTableRatesProvider
 */
class ShippingProvider implements AttributeHandlerInterface
{
    private const CONDITION_PACKAGE_WEIGHT = 'package_weight';
    private const CONDITION_PACKAGE_VALUE = 'package_value';
    private const CONDITION_PACKAGE_VALUE_WITH_DISCOUNT = 'package_value_with_discount';
    private const CONDITION_PACKAGE_QTY = 'package_qty';
    private const SINGLE_ITEM_QTY = 1.0;
    private const GOOGLE_SHIPPING_ENTRY_LIMIT = 100;

    private AllowedCountriesProvider $allowedCountriesProvider;
    private TableRateConditionProvider $tableRateConditionProvider;
    private ShippingTableRateQuery $shippingTableRateQuery;
    private CurrencyAmountProvider $currencyAmountProvider;
    private ResourceConnection $resourceConnection;
    private TableRateCarrierConfigProvider $tableRateCarrierConfigProvider;
    private MatchingTableRatesProvider $matchingTableRatesProvider;
    private StoreManagerInterface $storeManager;
    private LoggerInterface $logger;
    private SingleItemGuestCartPriceProvider $singleItemGuestCartPriceProvider;

    /**
     * @var array<int, array<RateDestination>>
     */
    private array $rateIndexByStore = [];

    /**
     * @var array<int, bool>
     */
    private array $truncationLoggedByStore = [];

    public function __construct(
        AllowedCountriesProvider $allowedCountriesProvider,
        TableRateConditionProvider $tableRateConditionProvider,
        ShippingTableRateQuery $shippingTableRateQuery,
        CurrencyAmountProvider $currencyAmountProvider,
        ResourceConnection $resourceConnection,
        TableRateCarrierConfigProvider $tableRateCarrierConfigProvider,
        MatchingTableRatesProvider $matchingTableRatesProvider,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        SingleItemGuestCartPriceProvider $singleItemGuestCartPriceProvider
    ) {
        $this->allowedCountriesProvider = $allowedCountriesProvider;
        $this->tableRateConditionProvider = $tableRateConditionProvider;
        $this->currencyAmountProvider = $currencyAmountProvider;
        $this->shippingTableRateQuery = $shippingTableRateQuery;
        $this->resourceConnection = $resourceConnection;
        $this->tableRateCarrierConfigProvider = $tableRateCarrierConfigProvider;
        $this->matchingTableRatesProvider = $matchingTableRatesProvider;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->singleItemGuestCartPriceProvider = $singleItemGuestCartPriceProvider;
    }

    /**
     * @return array<array{country: string, region?: string, price: string}>
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function get(Product $product): array
    {
        $storeId = (int)$product->getStoreId();

        if ($product->isVirtual() || !$this->tableRateCarrierConfigProvider->isActive($storeId)) {
            return [];
        }

        $conditionName = $this->tableRateConditionProvider->get($storeId);
        $conditionValue = $this->getConditionValue($product, $conditionName);

        if ($conditionValue === null) {
            return [];
        }

        $groupedRates = $this->matchingTableRatesProvider->getGroupedFromRateIndex(
            $this->getRateIndex($storeId, $conditionName),
            $conditionValue
        );

        $result = [];

        foreach ($this->limitToGoogleShippingEntryLimit($groupedRates, $storeId) as $matchingRate) {
            $result[] = $this->buildShippingEntry($matchingRate, $storeId);
        }

        return $result;
    }

    private function getConditionValue(Product $product, string $conditionName): ?float
    {
        if (
            $conditionName === self::CONDITION_PACKAGE_VALUE
            || $conditionName === self::CONDITION_PACKAGE_VALUE_WITH_DISCOUNT
        ) {
            return $this->singleItemGuestCartPriceProvider->get($product);
        }

        if ($conditionName === self::CONDITION_PACKAGE_WEIGHT) {
            return $this->getShippingWeight($product);
        }

        if ($conditionName === self::CONDITION_PACKAGE_QTY) {
            return self::SINGLE_ITEM_QTY;
        }

        return null;
    }

    /**
     * A configurable product ships as one of its children, so the heaviest enabled child is used.
     */
    private function getShippingWeight(Product $product): float
    {
        $weight = (float)$product->getWeight();
        $typeInstance = $product->getTypeInstance();

        if (!$typeInstance instanceof Configurable) {
            return $weight;
        }

        foreach ($typeInstance->getUsedProducts($product) as $childProduct) {
            if ((int)$childProduct->getStatus() !== Status::STATUS_ENABLED) {
                continue;
            }

            $weight = max($weight, (float)$childProduct->getWeight());
        }

        return $weight;
    }

    /**
     * @return array<RateDestination>
     * @throws NoSuchEntityException
     */
    private function getRateIndex(int $storeId, string $conditionName): array
    {
        if (isset($this->rateIndexByStore[$storeId])) {
            return $this->rateIndexByStore[$storeId];
        }

        $this->rateIndexByStore[$storeId] = $this->createRateIndex($storeId, $conditionName);

        return $this->rateIndexByStore[$storeId];
    }

    /**
     * @return array<RateDestination>
     * @throws NoSuchEntityException
     */
    private function createRateIndex(int $storeId, string $conditionName): array
    {
        $countries = $this->getShippingCountries($storeId);

        if ($countries === [] && $this->tableRateCarrierConfigProvider->isSpecificCountriesOnly($storeId)) {
            return [];
        }

        $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();
        $select = $this->shippingTableRateQuery->get($countries, $conditionName, $websiteId);
        $tableRates = $this->resourceConnection->getConnection()->fetchAll($select);

        return $this->matchingTableRatesProvider->createRateIndex($tableRates, $countries);
    }

    /**
     * @see \Magento\Shipping\Model\Carrier\AbstractCarrier::checkAvailableShipCountries()
     * @return string[]
     */
    private function getShippingCountries(int $storeId): array
    {
        $allowedCountries = $this->allowedCountriesProvider->get($storeId);

        if (!$this->tableRateCarrierConfigProvider->isSpecificCountriesOnly($storeId)) {
            return $allowedCountries;
        }

        $specificCountries = $this->tableRateCarrierConfigProvider->getSpecificCountries($storeId);

        if ($allowedCountries === []) {
            return $specificCountries;
        }

        return array_values(array_intersect($allowedCountries, $specificCountries));
    }

    /**
     * Rates applied from rows for any country are all dropped rather than cut off at an arbitrary country.
     *
     * @param array{country_rates: array<MatchingRate>, any_country_rates: array<MatchingRate>} $groupedRates
     * @return array<MatchingRate>
     */
    private function limitToGoogleShippingEntryLimit(array $groupedRates, int $storeId): array
    {
        $countryRates = $groupedRates['country_rates'];
        $entryCount = count($countryRates) + count($groupedRates['any_country_rates']);

        if ($entryCount <= self::GOOGLE_SHIPPING_ENTRY_LIMIT) {
            return array_merge($countryRates, $groupedRates['any_country_rates']);
        }

        if (!isset($this->truncationLoggedByStore[$storeId])) {
            $this->logger->warning(
                'Google Shopping feed: products have more shipping entries than Google\'s limit of 100, so the '
                . 'rates of table rate rows for all countries (*) were left out. Add table rate rows for your main '
                . 'countries or set up shipping in the Merchant Center account instead.',
                [ 'store_id' => $storeId, 'shipping_entries' => $entryCount ]
            );
            $this->truncationLoggedByStore[$storeId] = true;
        }

        return array_slice($countryRates, 0, self::GOOGLE_SHIPPING_ENTRY_LIMIT);
    }

    /**
     * @param MatchingRate $matchingRate
     * @return array{country: string, region?: string, price: string}
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function buildShippingEntry(array $matchingRate, int $storeId): array
    {
        $shippingEntry = [ 'country' => $matchingRate['country'] ];

        if ($matchingRate['region'] !== null) {
            $shippingEntry['region'] = $matchingRate['region'];
        }

        $shippingEntry['price'] = $this->currencyAmountProvider->get(
            $this->addHandlingFee($matchingRate['price'], $storeId),
            $storeId
        );

        return $shippingEntry;
    }

    /**
     * @see \Magento\Shipping\Model\Carrier\AbstractCarrier::getFinalPriceWithHandlingFee()
     */
    private function addHandlingFee(float $price, int $storeId): float
    {
        $handlingFee = $this->tableRateCarrierConfigProvider->getHandlingFee($storeId);

        if (
            $this->tableRateCarrierConfigProvider->getHandlingType($storeId)
            === TableRateCarrierConfigProvider::HANDLING_TYPE_PERCENT
        ) {
            return $price + $price * $handlingFee / 100;
        }

        return $price + $handlingFee;
    }
}
