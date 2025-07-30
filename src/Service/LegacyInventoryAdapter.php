<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Service;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class LegacyInventoryAdapter implements InventoryAdapterInterface
{
    private const DEFAULT_STOCK_ID = 1;

    /** @var array<int, int> */
    private array $stockIdCache = [];

    private StockRegistryInterface $stockRegistry;

    public function __construct(
        StockRegistryInterface $stockRegistry
    ) {
        $this->stockRegistry = $stockRegistry;
    }

    public function getAssignedStockIdForStore(int $storeId): int
    {
        if (isset($this->stockIdCache[$storeId])) {
            return $this->stockIdCache[$storeId];
        }

        $stockId = self::DEFAULT_STOCK_ID;
        $this->stockIdCache[$storeId] = $stockId;

        return $stockId;
    }

    public function areProductsSalable(array $skus, int $stockId): array
    {
        $results = [];

        foreach ($skus as $sku) {
            try {
                $stockStatus = $this->stockRegistry->getStockStatusBySku($sku);
                $isInStock = (bool) $stockStatus->getStockStatus();

                $results[] = new InventorySalableResult($sku, $isInStock);
            } catch (NoSuchEntityException $e) {
                $results[] = new InventorySalableResult(
                    $sku,
                    false,
                    ['Product not found: ' . $e->getMessage()]
                );
            } catch (LocalizedException $e) {
                $results[] = new InventorySalableResult(
                    $sku,
                    false,
                    ['Error checking stock: ' . $e->getMessage()]
                );
            }
        }

        return $results;
    }
}
