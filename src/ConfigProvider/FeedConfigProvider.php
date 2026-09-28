<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use RunAsRoot\GoogleShoppingFeed\SourceModel\ConfigurableExportType;
use function explode;

class FeedConfigProvider
{
    private const CONFIG_PATH_FEED_IS_ENABLED = 'run_as_root_product_feed/general/enabled';
    private const CONFIG_PATH_CATEGORY_WHITELIST = 'run_as_root_product_feed/general/category_whitelist';
    private const CONFIG_PATH_CATEGORY_BLACKLIST = 'run_as_root_product_feed/general/category_blacklist';
    private const CONFIG_PATH_CONFIGURABLE_EXPORT_TYPE =
        'run_as_root_product_feed/general/configurable_export_type';

    private ScopeConfigInterface $config;

    public function __construct(ScopeConfigInterface $config)
    {
        $this->config = $config;
    }

    public function isEnabled(int $storeId): bool
    {
        return $this->config->isSetFlag(self::CONFIG_PATH_FEED_IS_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getCategoryWhitelist(int $storeId): array
    {
        $categoriesWhitelistString = $this->config->getValue(
            self::CONFIG_PATH_CATEGORY_WHITELIST,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $categoriesWhitelistString !== null ? explode(',', $categoriesWhitelistString) : [];
    }

    public function getCategoryBlacklist(int $storeId): array
    {
        $categoriesBlacklistString = $this->config->getValue(
            self::CONFIG_PATH_CATEGORY_BLACKLIST,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $categoriesBlacklistString !== null ? explode(',', $categoriesBlacklistString) : [];
    }

    public function getConfigurableExportType(int $storeId): string
    {
        return (string) $this->config->getValue(
            self::CONFIG_PATH_CONFIGURABLE_EXPORT_TYPE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: ConfigurableExportType::EXPORT_CHILD_PRODUCTS;
    }
}
