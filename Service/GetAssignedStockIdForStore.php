<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySales\Model\ResourceModel\GetAssignedStockIdForWebsite;
use Magento\Store\Model\StoreManagerInterface;

class GetAssignedStockIdForStore
{
    private array $cache = [];
    private GetAssignedStockIdForWebsite $getAssignedStockIdForWebsite;
    private StoreManagerInterface $storeManager;

    public function __construct(
        GetAssignedStockIdForWebsite $getAssignedStockIdForWebsite,
        StoreManagerInterface $storeManager
    ) {
        $this->getAssignedStockIdForWebsite = $getAssignedStockIdForWebsite;
        $this->storeManager = $storeManager;
    }

    /**
     * @throws LocalizedException
     */
    public function execute(int $storeId): ?int
    {
        if (isset($this->cache[$storeId])) {
            return $this->cache[$storeId];
        }

        $store = $this->storeManager->getStore($storeId);
        $website = $this->storeManager->getWebsite($store->getWebsiteId());
        $websiteCode = $website->getCode();

        $res = $this->getAssignedStockIdForWebsite->execute($websiteCode);
        $this->cache[$storeId] = $res;

        return $res;
    }
}
