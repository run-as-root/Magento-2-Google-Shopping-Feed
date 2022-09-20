<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;

interface AttributeHandlerInterface
{
    /** @return mixed */
    public function get(Product $product);
}
