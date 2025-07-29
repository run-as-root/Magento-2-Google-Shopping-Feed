<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class MsiInventoryAdapter implements InventoryAdapterInterface
{
    /** @var array<int, int> */
    private array $stockIdCache = [];

    private $getAssignedStockIdForWebsite;
    private $areProductsSalable;
    private StoreManagerInterface $storeManager;

    public function __construct(
        $getAssignedStockIdForWebsite,
        $areProductsSalable,
        StoreManagerInterface $storeManager
    ) {
        $this->getAssignedStockIdForWebsite = $getAssignedStockIdForWebsite;
        $this->areProductsSalable = $areProductsSalable;
        $this->storeManager = $storeManager;
    }

    public function getAssignedStockIdForStore(int $storeId): int
    {
        if (isset($this->stockIdCache[$storeId])) {
            return $this->stockIdCache[$storeId];
        }

        $store = $this->storeManager->getStore($storeId);
        $website = $this->storeManager->getWebsite($store->getWebsiteId());
        $websiteCode = $website->getCode();

        $stockId = $this->getAssignedStockIdForWebsite->execute($websiteCode);
        $this->stockIdCache[$storeId] = $stockId;

        return $stockId;
    }

    public function areProductsSalable(array $skus, int $stockId): array
    {
        $msiResults = $this->areProductsSalable->execute($skus, $stockId);

        $results = [];
        foreach ($msiResults as $msiResult) {
            $results[] = new InventorySalableResult(
                $msiResult->getSku(),
                $msiResult->isSalable(),
                $msiResult->getErrors()
            );
        }

        return $results;
    }
}