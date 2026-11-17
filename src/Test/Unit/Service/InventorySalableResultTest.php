<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\Service;

use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\Service\InventorySalableResult;

class InventorySalableResultTest extends TestCase
{
    public function testErrorsDefaultToEmptyArray(): void
    {
        $sut = new InventorySalableResult('SKU1', true);

        $this->assertSame([], $sut->getErrors());
    }

    public function testReturnsGivenErrors(): void
    {
        $sut = new InventorySalableResult('SKU1', false, ['Out of stock']);

        $this->assertSame('SKU1', $sut->getSku());
        $this->assertFalse($sut->isSalable());
        $this->assertSame(['Out of stock'], $sut->getErrors());
    }
}
