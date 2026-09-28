<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Service;

class InventorySalableResult implements InventorySalableResultInterface
{
    private string $sku;
    private bool $isSalable;

    /** @var string[] */
    private array $errors;

    /**
     * @param string[] $errors
     */
    public function __construct(string $sku, bool $isSalable, array $errors = [])
    {
        $this->sku = $sku;
        $this->isSalable = $isSalable;
        $this->errors = $errors;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function isSalable(): bool
    {
        return $this->isSalable;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
