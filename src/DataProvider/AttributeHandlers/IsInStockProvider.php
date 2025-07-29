<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use RunAsRoot\GoogleShoppingFeed\Enum\GoogleShoppingAviabilityEnumInterface;
use RunAsRoot\GoogleShoppingFeed\Service\GetAssignedStockIdForStore;
use RunAsRoot\GoogleShoppingFeed\Service\InventoryAdapterFactory;
use RunAsRoot\GoogleShoppingFeed\Service\InventoryAdapterInterface;

class IsInStockProvider implements AttributeHandlerInterface
{
    private GetAssignedStockIdForStore $getAssignedStockIdForStore;
    private InventoryAdapterFactory $inventoryAdapterFactory;
    private ?InventoryAdapterInterface $inventoryAdapter = null;

    public function __construct(
        GetAssignedStockIdForStore $getAssignedStockIdForStore,
        InventoryAdapterFactory $inventoryAdapterFactory
    ) {
        $this->getAssignedStockIdForStore = $getAssignedStockIdForStore;
        $this->inventoryAdapterFactory = $inventoryAdapterFactory;
    }

    public function get(Product $product): string
    {
        $store = $product->getStore();

        try {
            $stockId = $this->getAssignedStockIdForStore->execute((int)$store->getId());
        } catch (LocalizedException $exception) {
            return GoogleShoppingAviabilityEnumInterface::OUT_OF_STOCK;
        }

        if ($stockId === null) {
            return GoogleShoppingAviabilityEnumInterface::OUT_OF_STOCK;
        }

        try {
            $inventoryAdapter = $this->getInventoryAdapter();
            $salableResults = $inventoryAdapter->areProductsSalable([$product->getSku()], $stockId);

            $salableResult = reset($salableResults);

            if ($salableResult && $salableResult->isSalable() === true) {
                return GoogleShoppingAviabilityEnumInterface::IN_STOCK;
            }
        } catch (LocalizedException $exception) {
            return GoogleShoppingAviabilityEnumInterface::OUT_OF_STOCK;
        }

        return GoogleShoppingAviabilityEnumInterface::OUT_OF_STOCK;
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
