<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Enum;

interface GoogleShoppingAviabilityEnumInterface
{
    public const IN_STOCK = 'in_stock';
    public const OUT_OF_STOCK = 'out_of_stock';
    public const PREORDER = 'preorder';
    public const BACKORDER = 'backorder';
}
