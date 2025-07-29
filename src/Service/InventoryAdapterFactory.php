<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Service;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use RunAsRoot\GoogleShoppingFeed\Exception\InventorySystemUnavailableException;

class InventoryAdapterFactory
{
    private const MSI_INVENTORY_SALES_MODULE = 'Magento_InventorySales';
    private const MSI_GET_ASSIGNED_STOCK_CLASS = 'Magento\InventorySales\Model\ResourceModel\GetAssignedStockIdForWebsite';
    private const MSI_ARE_PRODUCTS_SALABLE_CLASS = 'Magento\InventorySales\Model\AreProductsSalable';

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

    private function isMsiAvailable(): bool
    {
        // Check if required MSI classes exist
        if (!class_exists(self::MSI_GET_ASSIGNED_STOCK_CLASS) 
            || !class_exists(self::MSI_ARE_PRODUCTS_SALABLE_CLASS)) {
            return false;
        }

        // Try to create the services to ensure they're properly configured in DI
        try {
            $this->objectManager->create(self::MSI_GET_ASSIGNED_STOCK_CLASS);
            $this->objectManager->create(self::MSI_ARE_PRODUCTS_SALABLE_CLASS);
            return true;
        } catch (\Throwable $e) {
            // MSI classes exist but can't be instantiated (likely module disabled)
            return false;
        }
    }

    protected function isLegacyInventoryAvailable(): bool
    {
        // Check if CatalogInventory interfaces are available
        return interface_exists(StockRegistryInterface::class);
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