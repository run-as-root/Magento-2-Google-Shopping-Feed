<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\AllowedCountriesProvider;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\TableRateCarrierConfigProvider;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\TableRateConditionProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\CurrencyAmountProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\MatchingTableRatesProvider;
use RunAsRoot\GoogleShoppingFeed\Query\ShippingTableRateQuery;

class ShippingProvider implements AttributeHandlerInterface
{
    private const CONDITION_PACKAGE_WEIGHT = 'package_weight';
    private const CONDITION_PACKAGE_VALUE = 'package_value';
    private const CONDITION_PACKAGE_VALUE_WITH_DISCOUNT = 'package_value_with_discount';
    private const CONDITION_PACKAGE_QTY = 'package_qty';
    private const SINGLE_ITEM_QTY = 1.0;

    private AllowedCountriesProvider $allowedCountriesProvider;
    private TableRateConditionProvider $tableRateConditionProvider;
    private ShippingTableRateQuery $shippingTableRateQuery;
    private CurrencyAmountProvider $currencyAmountProvider;
    private ResourceConnection $resourceConnection;
    private TableRateCarrierConfigProvider $tableRateCarrierConfigProvider;
    private MatchingTableRatesProvider $matchingTableRatesProvider;
    private StoreManagerInterface $storeManager;

    /**
     * @var array<int, array<array<string, mixed>>>
     */
    private array $tableRatesByStore = [];

    public function __construct(
        AllowedCountriesProvider $allowedCountriesProvider,
        TableRateConditionProvider $tableRateConditionProvider,
        ShippingTableRateQuery $shippingTableRateQuery,
        CurrencyAmountProvider $currencyAmountProvider,
        ResourceConnection $resourceConnection,
        TableRateCarrierConfigProvider $tableRateCarrierConfigProvider,
        MatchingTableRatesProvider $matchingTableRatesProvider,
        StoreManagerInterface $storeManager
    ) {
        $this->allowedCountriesProvider = $allowedCountriesProvider;
        $this->tableRateConditionProvider = $tableRateConditionProvider;
        $this->currencyAmountProvider = $currencyAmountProvider;
        $this->shippingTableRateQuery = $shippingTableRateQuery;
        $this->resourceConnection = $resourceConnection;
        $this->tableRateCarrierConfigProvider = $tableRateCarrierConfigProvider;
        $this->matchingTableRatesProvider = $matchingTableRatesProvider;
        $this->storeManager = $storeManager;
    }

    /**
     * @return array<array{country: string, region?: string, price: string}>
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function get(Product $product): array
    {
        $storeId = (int)$product->getStoreId();

        if (!$this->tableRateCarrierConfigProvider->isActive($storeId)) {
            return [];
        }

        $conditionName = $this->tableRateConditionProvider->get($storeId);
        $conditionValue = $this->getConditionValue($product, $conditionName);

        if ($conditionValue === null) {
            return [];
        }

        $countries = $this->allowedCountriesProvider->get($storeId);
        $matchingRates = $this->matchingTableRatesProvider->get(
            $this->getTableRates($storeId, $countries, $conditionName),
            $countries,
            $conditionValue
        );

        $result = [];

        foreach ($matchingRates as $matchingRate) {
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
            return (float)$product->getWeight();
        }

        if ($conditionName === self::CONDITION_PACKAGE_QTY) {
            return self::SINGLE_ITEM_QTY;
        }

        return null;
    }

    /**
     * @param string[] $countries
     * @return array<array<string, mixed>>
     * @throws NoSuchEntityException
     */
    private function getTableRates(int $storeId, array $countries, string $conditionName): array
    {
        if (isset($this->tableRatesByStore[$storeId])) {
            return $this->tableRatesByStore[$storeId];
        }

        $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();
        $select = $this->shippingTableRateQuery->get($countries, $conditionName, $websiteId);
        $this->tableRatesByStore[$storeId] = $this->resourceConnection->getConnection()->fetchAll($select);

        return $this->tableRatesByStore[$storeId];
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
