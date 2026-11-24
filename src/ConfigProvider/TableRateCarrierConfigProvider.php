<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

use function array_filter;
use function array_values;
use function explode;

class TableRateCarrierConfigProvider
{
    public const HANDLING_TYPE_FIXED = 'F';
    public const HANDLING_TYPE_PERCENT = 'P';
    private const CONFIG_PATH_ACTIVE = 'carriers/tablerate/active';
    private const CONFIG_PATH_HANDLING_TYPE = 'carriers/tablerate/handling_type';
    private const CONFIG_PATH_HANDLING_FEE = 'carriers/tablerate/handling_fee';
    private const CONFIG_PATH_SPECIFIC_COUNTRIES_ONLY = 'carriers/tablerate/sallowspecific';
    private const CONFIG_PATH_SPECIFIC_COUNTRIES = 'carriers/tablerate/specificcountry';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function isActive(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getHandlingType(int $storeId): string
    {
        $handlingType = (string)$this->scopeConfig->getValue(
            self::CONFIG_PATH_HANDLING_TYPE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $handlingType !== '' ? $handlingType : self::HANDLING_TYPE_FIXED;
    }

    public function getHandlingFee(int $storeId): float
    {
        return (float)$this->scopeConfig->getValue(
            self::CONFIG_PATH_HANDLING_FEE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isSpecificCountriesOnly(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_SPECIFIC_COUNTRIES_ONLY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @return string[]
     */
    public function getSpecificCountries(int $storeId): array
    {
        $configValue = (string)$this->scopeConfig->getValue(
            self::CONFIG_PATH_SPECIFIC_COUNTRIES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return array_values(array_filter(explode(',', $configValue)));
    }
}
