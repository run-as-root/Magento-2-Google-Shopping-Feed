<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\Service;

use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\Service\InventorySalableResult;
use RunAsRoot\GoogleShoppingFeed\Service\LegacyInventoryAdapter;

class LegacyInventoryAdapterTest extends TestCase
{
    /** @var StockRegistryInterface|MockObject */
    private $stockRegistryMock;

    private LegacyInventoryAdapter $legacyInventoryAdapter;

    protected function setUp(): void
    {
        $this->stockRegistryMock = $this->createMock(StockRegistryInterface::class);

        $this->legacyInventoryAdapter = new LegacyInventoryAdapter(
            $this->stockRegistryMock
        );
    }

    public function testGetAssignedStockIdForStoreReturnsDefaultStockId(): void
    {
        $storeId = 1;
        $expectedStockId = 1; // Default stock ID

        $result = $this->legacyInventoryAdapter->getAssignedStockIdForStore($storeId);

        $this->assertEquals($expectedStockId, $result);
    }

    public function testGetAssignedStockIdForStoreUsesCaching(): void
    {
        $storeId = 1;
        $expectedStockId = 1;

        // Call twice to test caching
        $result1 = $this->legacyInventoryAdapter->getAssignedStockIdForStore($storeId);
        $result2 = $this->legacyInventoryAdapter->getAssignedStockIdForStore($storeId);

        $this->assertEquals($expectedStockId, $result1);
        $this->assertEquals($expectedStockId, $result2);
    }

    public function testAreProductsSalableReturnsCorrectResults(): void
    {
        $skus = ['SKU1', 'SKU2'];
        $stockId = 1;

        $stockStatus1Mock = $this->createMock(StockStatusInterface::class);
        $stockStatus2Mock = $this->createMock(StockStatusInterface::class);

        $stockStatus1Mock->method('getStockStatus')->willReturn(1); // In stock
        $stockStatus2Mock->method('getStockStatus')->willReturn(0); // Out of stock

        $this->stockRegistryMock
            ->expects($this->exactly(2))
            ->method('getStockStatusBySku')
            ->willReturnMap([
                ['SKU1', null, $stockStatus1Mock],
                ['SKU2', null, $stockStatus2Mock],
            ]);

        $results = $this->legacyInventoryAdapter->areProductsSalable($skus, $stockId);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(InventorySalableResult::class, $results[0]);
        $this->assertInstanceOf(InventorySalableResult::class, $results[1]);

        $this->assertEquals('SKU1', $results[0]->getSku());
        $this->assertTrue($results[0]->isSalable());
        $this->assertEmpty($results[0]->getErrors());

        $this->assertEquals('SKU2', $results[1]->getSku());
        $this->assertFalse($results[1]->isSalable());
        $this->assertEmpty($results[1]->getErrors());
    }

    public function testAreProductsSalableHandlesProductNotFound(): void
    {
        $skus = ['NONEXISTENT_SKU'];
        $stockId = 1;

        $this->stockRegistryMock
            ->expects($this->once())
            ->method('getStockStatusBySku')
            ->with('NONEXISTENT_SKU', null)
            ->willThrowException(new NoSuchEntityException(__('Product not found')));

        $results = $this->legacyInventoryAdapter->areProductsSalable($skus, $stockId);

        $this->assertCount(1, $results);
        $this->assertInstanceOf(InventorySalableResult::class, $results[0]);

        $this->assertEquals('NONEXISTENT_SKU', $results[0]->getSku());
        $this->assertFalse($results[0]->isSalable());
        $this->assertContains('Product not found: Product not found', $results[0]->getErrors());
    }

    public function testAreProductsSalableHandlesLocalizedException(): void
    {
        $skus = ['ERROR_SKU'];
        $stockId = 1;

        $this->stockRegistryMock
            ->expects($this->once())
            ->method('getStockStatusBySku')
            ->with('ERROR_SKU', null)
            ->willThrowException(new LocalizedException(__('Stock registry error')));

        $results = $this->legacyInventoryAdapter->areProductsSalable($skus, $stockId);

        $this->assertCount(1, $results);
        $this->assertInstanceOf(InventorySalableResult::class, $results[0]);

        $this->assertEquals('ERROR_SKU', $results[0]->getSku());
        $this->assertFalse($results[0]->isSalable());
        $this->assertContains('Error checking stock: Stock registry error', $results[0]->getErrors());
    }

    public function testAreProductsSalableWithMixedResults(): void
    {
        $skus = ['GOOD_SKU', 'BAD_SKU', 'ERROR_SKU'];
        $stockId = 1;

        $stockStatusMock = $this->createMock(StockStatusInterface::class);
        $stockStatusMock->method('getStockStatus')->willReturn(1);

        $this->stockRegistryMock
            ->expects($this->exactly(3))
            ->method('getStockStatusBySku')
            ->willReturnCallback(function ($sku) use ($stockStatusMock) {
                switch ($sku) {
                    case 'GOOD_SKU':
                        return $stockStatusMock;
                    case 'BAD_SKU':
                        throw new NoSuchEntityException(__('Product not found'));
                    case 'ERROR_SKU':
                        throw new LocalizedException(__('Database error'));
                    default:
                        throw new \InvalidArgumentException('Unexpected SKU');
                }
            });

        $results = $this->legacyInventoryAdapter->areProductsSalable($skus, $stockId);

        $this->assertCount(3, $results);

        // Good SKU - should be in stock
        $this->assertEquals('GOOD_SKU', $results[0]->getSku());
        $this->assertTrue($results[0]->isSalable());
        $this->assertEmpty($results[0]->getErrors());

        // Bad SKU - should be out of stock with product not found error
        $this->assertEquals('BAD_SKU', $results[1]->getSku());
        $this->assertFalse($results[1]->isSalable());
        $this->assertNotEmpty($results[1]->getErrors());

        // Error SKU - should be out of stock with database error
        $this->assertEquals('ERROR_SKU', $results[2]->getSku());
        $this->assertFalse($results[2]->isSalable());
        $this->assertNotEmpty($results[2]->getErrors());
    }
}