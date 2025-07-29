<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Service;

use Magento\Framework\Exception\LocalizedException;

class GetAssignedStockIdForStore
{
    /** @var array<int, ?int> */
    private array $cache = [];
    private InventoryAdapterFactory $inventoryAdapterFactory;
    private ?InventoryAdapterInterface $inventoryAdapter = null;

    public function __construct(
        InventoryAdapterFactory $inventoryAdapterFactory
    ) {
        $this->inventoryAdapterFactory = $inventoryAdapterFactory;
    }

    /**
     * @throws LocalizedException
     */
    public function execute(int $storeId): ?int
    {
        if (isset($this->cache[$storeId])) {
            return $this->cache[$storeId];
        }

        $inventoryAdapter = $this->getInventoryAdapter();
        $stockId = $inventoryAdapter->getAssignedStockIdForStore($storeId);
        $this->cache[$storeId] = $stockId;

        return $stockId;
    }

    /**
     * @throws LocalizedException
     */
    private function getInventoryAdapter(): InventoryAdapterInterface
    {
        if ($this->inventoryAdapter === null) {
            $this->inventoryAdapter = $this->inventoryAdapterFactory->create();
        }

        return $this->inventoryAdapter;
    }
}
