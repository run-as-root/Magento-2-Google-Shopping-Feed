<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Service;

interface InventorySalableResultInterface
{
    public function getSku(): string;

    public function isSalable(): bool;

    /**
     * @return string[]
     */
    public function getErrors(): array;
}
