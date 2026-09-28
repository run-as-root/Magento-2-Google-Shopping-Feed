<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Service;

use Magento\Framework\Exception\LocalizedException;

interface InventoryAdapterInterface
{
    /**
     * @throws LocalizedException
     */
    public function getAssignedStockIdForStore(int $storeId): int;

    /**
     * @param string[] $skus
     * @throws LocalizedException
     * @return InventorySalableResultInterface[]
     */
    public function areProductsSalable(array $skus, int $stockId): array;
}
