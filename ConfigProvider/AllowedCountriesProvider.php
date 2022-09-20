<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

use function explode;
use function is_array;

class AllowedCountriesProvider
{
    private const CONFIG_PATH = 'general/country/allow';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function get(int $storeId): array
    {
        $configValue = $this->scopeConfig->getValue(self::CONFIG_PATH, ScopeInterface::SCOPE_STORE, $storeId);

        if (empty($configValue)) {
            return [];
        }

        $countryCodes = explode(',', $configValue);

        if (!is_array($countryCodes)) {
            return [];
        }

        return $countryCodes;
    }
}
