<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySales\Model\AreProductsSalable;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;
use RunAsRoot\Feed\Enum\GoogleShoppingAviabilityEnumInterface;
use RunAsRoot\Feed\Service\GetAssignedStockIdForStore;

class IsInStockProvider implements AttributeHandlerInterface
{
    private GetAssignedStockIdForStore $getAssignedStockIdForStore;
    private AreProductsSalable $areProductsSalable;

    public function __construct(
        GetAssignedStockIdForStore $getAssignedStockIdForStore,
        AreProductsSalable $areProductsSalable
    ) {
        $this->getAssignedStockIdForStore = $getAssignedStockIdForStore;
        $this->areProductsSalable = $areProductsSalable;
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

        /** @var IsProductSalableResultInterface[] $allSalableInformation */
        $allSalableInformation = $this->areProductsSalable->execute([$product->getSku()], $stockId);

        $salableInformation = reset($allSalableInformation);

        if ($salableInformation && $salableInformation->isSalable() === true) {
            return GoogleShoppingAviabilityEnumInterface::IN_STOCK;
        }

        return GoogleShoppingAviabilityEnumInterface::OUT_OF_STOCK;
    }
}
