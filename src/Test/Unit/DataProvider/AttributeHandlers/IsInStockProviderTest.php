<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\IsInStockProvider;
use RunAsRoot\GoogleShoppingFeed\Service\GetAssignedStockIdForStore;
use RunAsRoot\GoogleShoppingFeed\Service\InventoryAdapterFactory;
use RunAsRoot\GoogleShoppingFeed\Service\InventoryAdapterInterface;
use RunAsRoot\GoogleShoppingFeed\Service\InventorySalableResult;

final class IsInStockProviderTest extends TestCase
{
    private IsInStockProvider $sut;

    /** @var GetAssignedStockIdForStore|MockObject */
    private $getAssignedStockIdForStoreMock;

    /** @var InventoryAdapterFactory|MockObject */
    private $inventoryAdapterFactoryMock;

    /** @var InventoryAdapterInterface|MockObject */
    private $inventoryAdapterMock;

    protected function setUp(): void
    {
        $this->getAssignedStockIdForStoreMock = $this->createMock(GetAssignedStockIdForStore::class);
        $this->inventoryAdapterFactoryMock = $this->createMock(InventoryAdapterFactory::class);
        $this->inventoryAdapterMock = $this->createMock(InventoryAdapterInterface::class);
        
        $this->sut = new IsInStockProvider(
            $this->getAssignedStockIdForStoreMock,
            $this->inventoryAdapterFactoryMock
        );
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGet(bool $isSalable, string $expected): void
    {
        $productMock = $this->createMock(Product::class);
        $storeMock = $this->createMock(Store::class);

        $productMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $storeId = 100;
        $storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $stockId = 200;
        $this->getAssignedStockIdForStoreMock
            ->expects($this->once())
            ->method('execute')
            ->with($storeId)
            ->willReturn($stockId);

        $productSku = 'product-sku';
        $productMock->expects($this->once())
            ->method('getSku')
            ->willReturn($productSku);

        $this->inventoryAdapterFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->inventoryAdapterMock);

        $salableResult = new InventorySalableResult($productSku, $isSalable);
        $this->inventoryAdapterMock
            ->expects($this->once())
            ->method('areProductsSalable')
            ->with([$productSku], $stockId)
            ->willReturn([$salableResult]);

        $this->assertEquals($expected, $this->sut->get($productMock));
    }

    public function dataProvider(): array
    {
        return [
            [true, 'in_stock'],
            [false, 'out_of_stock'],
        ];
    }

    public function testItShouldReturnOutOfStockWhenTheStockServiceThrowAnException(): void
    {
        $productMock = $this->createMock(Product::class);
        $storeMock = $this->createMock(Store::class);

        $productMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $storeId = 100;
        $storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $this->getAssignedStockIdForStoreMock
            ->expects($this->once())
            ->method('execute')
            ->with($storeId)
            ->willThrowException(new LocalizedException(new Phrase('')));

        $this->assertEquals('out_of_stock', $this->sut->get($productMock));
    }

    public function testItShouldReturnOutOfStockWhenThereIsNoStockSourceConfiguredForTheStoreId(): void
    {
        $productMock = $this->createMock(Product::class);
        $storeMock = $this->createMock(Store::class);

        $productMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $storeId = 100;
        $storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $this->getAssignedStockIdForStoreMock
            ->expects($this->once())
            ->method('execute')
            ->with($storeId)
            ->willReturn(null);

        $this->assertEquals('out_of_stock', $this->sut->get($productMock));
    }

    public function testItShouldReturnOutOfStockWhenInventoryAdapterThrowsException(): void
    {
        $productMock = $this->createMock(Product::class);
        $storeMock = $this->createMock(Store::class);

        $productMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $storeId = 100;
        $stockId = 200;
        $productSku = 'product-sku';

        $storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $this->getAssignedStockIdForStoreMock
            ->expects($this->once())
            ->method('execute')
            ->with($storeId)
            ->willReturn($stockId);

        $productMock->expects($this->once())
            ->method('getSku')
            ->willReturn($productSku);

        $this->inventoryAdapterFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->inventoryAdapterMock);

        $this->inventoryAdapterMock
            ->expects($this->once())
            ->method('areProductsSalable')
            ->with([$productSku], $stockId)
            ->willThrowException(new LocalizedException(new Phrase('Inventory error')));

        $this->assertEquals('out_of_stock', $this->sut->get($productMock));
    }
}
