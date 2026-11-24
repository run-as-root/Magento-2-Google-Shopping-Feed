<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class TableRateCarrierConfigProvider
{
    public const HANDLING_TYPE_FIXED = 'F';
    public const HANDLING_TYPE_PERCENT = 'P';
    private const CONFIG_PATH_ACTIVE = 'carriers/tablerate/active';
    private const CONFIG_PATH_HANDLING_TYPE = 'carriers/tablerate/handling_type';
    private const CONFIG_PATH_HANDLING_FEE = 'carriers/tablerate/handling_fee';

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
}
