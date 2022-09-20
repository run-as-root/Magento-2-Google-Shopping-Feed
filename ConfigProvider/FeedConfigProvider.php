<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use function explode;

class FeedConfigProvider
{
    private const CONFIG_PATH_FEED_IS_ENABLED = 'run_as_root_product_feed/general/enabled';
    private const CONFIG_PATH_CATEGORY_WHITELIST = 'run_as_root_product_feed/general/category_whitelist';
    private const CONFIG_PATH_CATEGORY_BLACKLIST = 'run_as_root_product_feed/general/category_blacklist';

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
}
