<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Service;

interface InventorySalableResultInterface
{
    /**
     * Get product SKU
     */
    public function getSku(): string;

    /**
     * Check if product is salable
     */
    public function isSalable(): bool;

    /**
     * Get salability errors if any
     *
     * @return string[]
     */
    public function getErrors(): array;
}