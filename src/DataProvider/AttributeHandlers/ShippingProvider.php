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
use RunAsRoot\GoogleShoppingFeed\Query\ShippingTableRateQuery;
use function array_intersect;
use function array_slice;
use function array_values;
use function count;
use function max;

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

    /**
     * @var array<int, array<array{country: string, region_id: int, region: ?string, rates: array<array<string, mixed>>}>>
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
        LoggerInterface $logger
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

        $matchingRates = $this->matchingTableRatesProvider->getFromRateIndex(
            $this->getRateIndex($storeId, $conditionName),
            $conditionValue
        );

        $result = [];

        foreach ($this->limitToGoogleShippingEntryLimit($matchingRates, $storeId) as $matchingRate) {
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
            return (float)$product->getFinalPrice();
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
     * @return array<array{country: string, region_id: int, region: ?string, rates: array<array<string, mixed>>}>
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
     * @return array<array{country: string, region_id: int, region: ?string, rates: array<array<string, mixed>>}>
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
     * @param array<array{country: string, region: ?string, price: float}> $matchingRates
     * @return array<array{country: string, region: ?string, price: float}>
     */
    private function limitToGoogleShippingEntryLimit(array $matchingRates, int $storeId): array
    {
        if (count($matchingRates) <= self::GOOGLE_SHIPPING_ENTRY_LIMIT) {
            return $matchingRates;
        }

        if (!isset($this->truncationLoggedByStore[$storeId])) {
            $this->logger->warning(
                'Google Shopping feed: shipping entries were truncated to Google\'s limit of 100 per product.',
                [ 'store_id' => $storeId, 'shipping_entries' => count($matchingRates) ]
            );
            $this->truncationLoggedByStore[$storeId] = true;
        }

        return array_slice($matchingRates, 0, self::GOOGLE_SHIPPING_ENTRY_LIMIT);
    }

    /**
     * @param array{country: string, region: ?string, price: float} $matchingRate
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
