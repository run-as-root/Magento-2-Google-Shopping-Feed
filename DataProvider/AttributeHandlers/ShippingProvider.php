<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\AllowedCountriesProvider;
use RunAsRoot\GoogleShoppingFeed\ConfigProvider\TableRateConditionProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\CurrencyAmountProvider;
use RunAsRoot\GoogleShoppingFeed\Query\ShippingTableRateQuery;

class ShippingProvider implements AttributeHandlerInterface
{
    private AllowedCountriesProvider $allowedCountriesProvider;
    private TableRateConditionProvider $tableRateConditionProvider;
    private ShippingTableRateQuery $shippingTableRateQuery;
    private CurrencyAmountProvider $currencyAmountProvider;
    private ResourceConnection $resourceConnection;

    public function __construct(
        AllowedCountriesProvider $allowedCountriesProvider,
        TableRateConditionProvider $tableRateConditionProvider,
        ShippingTableRateQuery $shippingTableRateQuery,
        CurrencyAmountProvider $currencyAmountProvider,
        ResourceConnection $resourceConnection
    ) {
        $this->allowedCountriesProvider = $allowedCountriesProvider;
        $this->tableRateConditionProvider = $tableRateConditionProvider;
        $this->currencyAmountProvider = $currencyAmountProvider;
        $this->shippingTableRateQuery = $shippingTableRateQuery;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function get(Product $product): array
    {
        $storeId = (int)$product->getStoreId();
        $countries = $this->allowedCountriesProvider->get($storeId);
        $conditionName = $this->tableRateConditionProvider->get($storeId);
        $select = $this->shippingTableRateQuery->get($countries, $conditionName);
        $shippingTableRates = $this->resourceConnection->getConnection()->fetchAll($select);

        $result = [];
        foreach ($shippingTableRates as $tableRate) {
            $price = $this->currencyAmountProvider->get((float)$tableRate['price'], $storeId);
            $result[] = [
                'country' => $tableRate['dest_country_id'],
                'price' => $price
            ];
        }

        return $result;
    }
}
