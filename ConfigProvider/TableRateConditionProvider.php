<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class TableRateConditionProvider
{
    private const CONFIG_PATH = 'carriers/tablerate/condition_name';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function get(int $storeId): string
    {
        return (string)$this->scopeConfig->getValue(self::CONFIG_PATH, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
