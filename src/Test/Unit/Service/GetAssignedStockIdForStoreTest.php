<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\Service;

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\Service\GetAssignedStockIdForStore;
use RunAsRoot\GoogleShoppingFeed\Service\InventoryAdapterFactory;
use RunAsRoot\GoogleShoppingFeed\Service\InventoryAdapterInterface;

final class GetAssignedStockIdForStoreTest extends TestCase
{
    /** @var InventoryAdapterFactory|MockObject */
    private $inventoryAdapterFactoryMock;

    /** @var InventoryAdapterInterface|MockObject */
    private $inventoryAdapterMock;

    private GetAssignedStockIdForStore $sut;

    protected function setUp(): void
    {
        $this->inventoryAdapterFactoryMock = $this->createMock(InventoryAdapterFactory::class);
        $this->inventoryAdapterMock = $this->createMock(InventoryAdapterInterface::class);

        $this->sut = new GetAssignedStockIdForStore(
            $this->inventoryAdapterFactoryMock
        );
    }

    public function testLocalizedExceptionIsThrown(): void
    {
        $storeId = 1;

        $this->inventoryAdapterFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willThrowException(new LocalizedException(__('Inventory system unavailable')));

        $this->expectException(LocalizedException::class);

        $this->sut->execute($storeId);
    }

    public function testGettingAssignedStockId(): void
    {
        $storeId = 1;
        $expectedStockId = 3;

        $this->inventoryAdapterFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->inventoryAdapterMock);

        $this->inventoryAdapterMock
            ->expects($this->once())
            ->method('getAssignedStockIdForStore')
            ->with($storeId)
            ->willReturn($expectedStockId);

        $result = $this->sut->execute($storeId);

        $this->assertEquals($expectedStockId, $result);
    }

    public function testCachingBehavior(): void
    {
        $storeId = 1;
        $expectedStockId = 3;

        $this->inventoryAdapterFactoryMock
            ->expects($this->once()) // Should only be called once due to caching
            ->method('create')
            ->willReturn($this->inventoryAdapterMock);

        $this->inventoryAdapterMock
            ->expects($this->once()) // Should only be called once due to caching
            ->method('getAssignedStockIdForStore')
            ->with($storeId)
            ->willReturn($expectedStockId);

        // Call twice to test caching
        $result1 = $this->sut->execute($storeId);
        $result2 = $this->sut->execute($storeId);

        $this->assertEquals($expectedStockId, $result1);
        $this->assertEquals($expectedStockId, $result2);
    }
}
