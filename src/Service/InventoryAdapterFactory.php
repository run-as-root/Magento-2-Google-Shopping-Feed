<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Service;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventorySales\Model\AreProductsSalable;
use Magento\InventorySales\Model\ResourceModel\GetAssignedStockIdForWebsite;
use Magento\Store\Model\StoreManagerInterface;
use RunAsRoot\GoogleShoppingFeed\Exception\InventorySystemUnavailableException;

class InventoryAdapterFactory
{
    private const STRING MSI_GET_ASSIGNED_STOCK_CLASS = GetAssignedStockIdForWebsite::class;
    private const STRING MSI_ARE_PRODUCTS_SALABLE_CLASS = AreProductsSalable::class;

    private ComponentRegistrar $componentRegistrar;
    private ObjectManagerInterface $objectManager;
    private StoreManagerInterface $storeManager;
    private StockRegistryInterface $stockRegistry;

    public function __construct(
        ComponentRegistrar $componentRegistrar,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        StockRegistryInterface $stockRegistry
    ) {
        $this->componentRegistrar = $componentRegistrar;
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @throws InventorySystemUnavailableException
     */
    public function create(): InventoryAdapterInterface
    {
        if ($this->isMsiAvailable()) {
            return $this->createMsiAdapter();
        }

        if ($this->isLegacyInventoryAvailable()) {
            return $this->createLegacyAdapter();
        }

        throw new InventorySystemUnavailableException(
            __('Neither MSI nor legacy inventory system is available.')
        );
    }

    protected function isLegacyInventoryAvailable(): bool
    {
        return interface_exists(StockRegistryInterface::class);
    }

    private function isMsiAvailable(): bool
    {
        if (
            !class_exists(self::MSI_GET_ASSIGNED_STOCK_CLASS)
            || !class_exists(self::MSI_ARE_PRODUCTS_SALABLE_CLASS)
        ) {
            return false;
        }

        try {
            $this->objectManager->create(self::MSI_GET_ASSIGNED_STOCK_CLASS);
            $this->objectManager->create(self::MSI_ARE_PRODUCTS_SALABLE_CLASS);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function createMsiAdapter(): MsiInventoryAdapter
    {
        $getAssignedStockIdForWebsite = $this->objectManager->create(self::MSI_GET_ASSIGNED_STOCK_CLASS);
        $areProductsSalable = $this->objectManager->create(self::MSI_ARE_PRODUCTS_SALABLE_CLASS);

        return new MsiInventoryAdapter(
            $getAssignedStockIdForWebsite,
            $areProductsSalable,
            $this->storeManager
        );
    }

    private function createLegacyAdapter(): LegacyInventoryAdapter
    {
        return new LegacyInventoryAdapter(
            $this->stockRegistry
        );
    }
}
