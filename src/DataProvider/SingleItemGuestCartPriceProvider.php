<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Api\Data\GroupInterface;

use function min;

/**
 * Price of a product in a guest cart holding one item of it: special price, tier price for a quantity of 1
 * and catalog price rules of the NOT LOGGED IN group, as Magento's price model composes them.
 * A configurable product is priced as its cheapest enabled child.
 */
class SingleItemGuestCartPriceProvider
{
    private const CUSTOMER_GROUP_ID = 'customer_group_id';
    private const FINAL_PRICE = 'final_price';
    private const CALCULATED_FINAL_PRICE = 'calculated_final_price';
    private const SINGLE_ITEM_QTY = 1.0;

    public function get(Product $product): float
    {
        $typeInstance = $product->getTypeInstance();

        if (!$typeInstance instanceof Configurable) {
            return $this->getOwnPrice($product);
        }

        $lowestChildPrice = null;

        foreach ($typeInstance->getUsedProducts($product) as $childProduct) {
            if (!$childProduct instanceof Product || (int)$childProduct->getStatus() !== Status::STATUS_ENABLED) {
                continue;
            }

            $childPrice = $this->getOwnPrice($childProduct);
            $lowestChildPrice = $lowestChildPrice === null ? $childPrice : min($lowestChildPrice, $childPrice);
        }

        return $lowestChildPrice ?? $this->getOwnPrice($product);
    }

    /**
     * Works on a copy, so the final price that other feed attributes read from the product stays untouched.
     */
    private function getOwnPrice(Product $product): float
    {
        $guestProduct = clone $product;
        $guestProduct->setData(self::CUSTOMER_GROUP_ID, GroupInterface::NOT_LOGGED_IN_ID);
        $guestProduct->unsetData(self::FINAL_PRICE);
        $guestProduct->unsetData(self::CALCULATED_FINAL_PRICE);

        return (float)$guestProduct->getFinalPrice(self::SINGLE_ITEM_QTY);
    }
}
