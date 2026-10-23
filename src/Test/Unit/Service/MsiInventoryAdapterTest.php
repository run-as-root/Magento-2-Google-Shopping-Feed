<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\Service;

use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\Service\InventorySalableResult;
use RunAsRoot\GoogleShoppingFeed\Service\MsiInventoryAdapter;

class MsiInventoryAdapterTest extends TestCase
{
    public function testAreProductsSalableMapsResultsWithoutErrorSupport(): void
    {
        $salableResult = new class {
            public function getSku(): string
            {
                return 'SKU1';
            }

            public function isSalable(): bool
            {
                return true;
            }
        };
        $unsalableResult = new class {
            public function getSku(): string
            {
                return 'SKU2';
            }

            public function isSalable(): bool
            {
                return false;
            }
        };
        $areProductsSalable = new class ([$salableResult, $unsalableResult]) {
            public function __construct(private readonly array $results)
            {
            }

            public function execute(array $skus, int $stockId): array
            {
                return $this->results;
            }
        };
        $sut = new MsiInventoryAdapter(
            new class {
            },
            $areProductsSalable,
            $this->createMock(StoreManagerInterface::class)
        );

        $results = $sut->areProductsSalable(['SKU1', 'SKU2'], 1);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(InventorySalableResult::class, $results[0]);
        $this->assertSame('SKU1', $results[0]->getSku());
        $this->assertTrue($results[0]->isSalable());
        $this->assertSame([], $results[0]->getErrors());
        $this->assertSame('SKU2', $results[1]->getSku());
        $this->assertFalse($results[1]->isSalable());
        $this->assertSame([], $results[1]->getErrors());
    }
}
