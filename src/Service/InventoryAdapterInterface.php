<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Service;

use Magento\Framework\Exception\LocalizedException;

interface InventoryAdapterInterface
{
    /**
     * Get assigned stock ID for a given store
     *
     * @throws LocalizedException
     */
    public function getAssignedStockIdForStore(int $storeId): int;

    /**
     * Check if products are salable for the given stock
     *
     * @param string[] $skus
     * @throws LocalizedException
     * @return InventorySalableResultInterface[]
     */
    public function areProductsSalable(array $skus, int $stockId): array;
}