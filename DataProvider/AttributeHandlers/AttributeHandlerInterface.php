<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;

interface AttributeHandlerInterface
{
    /** @return mixed */
    public function get(Product $product);
}
