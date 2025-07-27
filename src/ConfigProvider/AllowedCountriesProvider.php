<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

use function explode;

class AllowedCountriesProvider
{
    private const CONFIG_PATH = 'general/country/allow';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string[]
     */
    public function get(int $storeId): array
    {
        $configValue = $this->scopeConfig->getValue(self::CONFIG_PATH, ScopeInterface::SCOPE_STORE, $storeId);

        if (empty($configValue)) {
            return [];
        }

        return explode(',', $configValue);
    }
}
