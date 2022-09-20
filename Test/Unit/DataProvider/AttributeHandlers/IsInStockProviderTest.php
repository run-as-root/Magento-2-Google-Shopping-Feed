<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\InventorySales\Model\AreProductsSalable;
use Magento\InventorySales\Model\IsProductSalableResult;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\IsInStockProvider;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\SimpleAttributeHandler;
use RunAsRoot\Feed\Service\GetAssignedStockIdForStore;

final class IsInStockProviderTest extends TestCase
{
    private IsInStockProvider $sut;

    /**
     * @var GetAssignedStockIdForStore|MockObject
     */
    private $getAssignedStockIdForStoreMock;

    /**
     * @var AreProductsSalable|MockObject
     */
    private $areProductsSalableMock;

    protected function setUp(): void
    {
        $this->getAssignedStockIdForStoreMock = $this->createMock(GetAssignedStockIdForStore::class);
        $this->areProductsSalableMock = $this->createMock(AreProductsSalable::class);
        $this->sut = new IsInStockProvider($this->getAssignedStockIdForStoreMock, $this->areProductsSalableMock);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGet(bool $isSalabled, string $expected): void
    {
        $productMock = $this->createMock(Product::class);
        $storeMock = $this->createMock(Store::class);
        $isProductSalableResultMock = $this->createMock(IsProductSalableResult::class);

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

        $this->areProductsSalableMock
            ->expects($this->once())
            ->method('execute')
            ->with([$productSku], $stockId)
            ->willReturn([$isProductSalableResultMock]);

        $isProductSalableResultMock->expects($this->once())
            ->method('isSalable')
            ->willReturn($isSalabled);

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
}
