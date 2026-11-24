<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type\Simple;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ProductPricesProvider;
use RunAsRoot\GoogleShoppingFeed\Service\GetAssignedStockIdForStore;
use RunAsRoot\GoogleShoppingFeed\Service\InventoryAdapterFactory;
use RunAsRoot\GoogleShoppingFeed\Service\InventoryAdapterInterface;
use RunAsRoot\GoogleShoppingFeed\Service\InventorySalableResult;

final class ProductPricesProviderTest extends TestCase
{
    private const STORE_ID = 3;
    private const STOCK_ID = 7;

    private ProductPricesProvider $sut;

    /**
     * @var GetAssignedStockIdForStore|MockObject
     */
    private $getAssignedStockIdForStoreMock;

    /**
     * @var InventoryAdapterInterface|MockObject
     */
    private $inventoryAdapterMock;

    protected function setUp(): void
    {
        $this->getAssignedStockIdForStoreMock = $this->createMock(GetAssignedStockIdForStore::class);
        $this->getAssignedStockIdForStoreMock->method('execute')->with(self::STORE_ID)->willReturn(self::STOCK_ID);
        $this->inventoryAdapterMock = $this->createMock(InventoryAdapterInterface::class);
        $inventoryAdapterFactoryMock = $this->createMock(InventoryAdapterFactory::class);
        $inventoryAdapterFactoryMock->method('create')->willReturn($this->inventoryAdapterMock);

        $this->sut = new ProductPricesProvider($this->getAssignedStockIdForStoreMock, $inventoryAdapterFactoryMock);
    }

    public function testGetReturnsOwnPricesForSingleNotLoggedInItem(): void
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getTypeInstance')->willReturn($this->createMock(Simple::class));
        $productMock->method('getPrice')->willReturn('34.000000');
        $productMock->expects($this->never())->method('getPriceInfo');

        $calls = [];
        $productMock->method('setData')
            ->willReturnCallback(static function ($key, $value) use (&$calls, $productMock) {
                $calls[] = $key . ':' . $value;
                return $productMock;
            });
        $productMock->method('getFinalPrice')
            ->willReturnCallback(static function ($qty) use (&$calls) {
                $calls[] = 'getFinalPrice:' . var_export($qty, true);
                return 29.0;
            });

        $result = $this->sut->get($productMock);

        $this->assertSame(34.0, $result->getRegularPrice());
        $this->assertSame(29.0, $result->getFinalPrice());
        $this->assertSame(
            ['customer_group_id:' . GroupInterface::NOT_LOGGED_IN_ID, 'getFinalPrice:1.0'],
            $calls
        );
    }

    public function testGetReturnsPricesOfCheapestSalableChildForConfigurable(): void
    {
        $cheapestChild = $this->createChildMock('cheapest', 60.0, 45.0, Status::STATUS_ENABLED);
        $otherChild = $this->createChildMock('other', 50.0, 50.0, Status::STATUS_ENABLED);
        $disabledChild = $this->createChildMock('disabled', 20.0, 10.0, Status::STATUS_DISABLED);
        $notSalableChild = $this->createChildMock('not-salable', 20.0, 15.0, Status::STATUS_ENABLED);

        $this->inventoryAdapterMock->expects($this->once())
            ->method('areProductsSalable')
            ->with(['other', 'cheapest', 'not-salable'], self::STOCK_ID)
            ->willReturn([
                new InventorySalableResult('other', true),
                new InventorySalableResult('cheapest', true),
                new InventorySalableResult('not-salable', false),
            ]);

        $result = $this->sut->get(
            $this->createConfigurableMock([$otherChild, $disabledChild, $cheapestChild, $notSalableChild])
        );

        $this->assertSame(60.0, $result->getRegularPrice());
        $this->assertSame(45.0, $result->getFinalPrice());
    }

    public function testGetPrefersLowerRegularPriceWhenFinalPricesAreEqual(): void
    {
        $higherRegularChild = $this->createChildMock('higher', 80.0, 40.0, Status::STATUS_ENABLED);
        $lowerRegularChild = $this->createChildMock('lower', 50.0, 40.0, Status::STATUS_ENABLED);
        $alsoHigherRegularChild = $this->createChildMock('also-higher', 90.0, 40.0, Status::STATUS_ENABLED);

        $this->inventoryAdapterMock->method('areProductsSalable')->willReturn([
            new InventorySalableResult('higher', true),
            new InventorySalableResult('lower', true),
            new InventorySalableResult('also-higher', true),
        ]);

        $result = $this->sut->get(
            $this->createConfigurableMock([$higherRegularChild, $lowerRegularChild, $alsoHigherRegularChild])
        );

        $this->assertSame(50.0, $result->getRegularPrice());
        $this->assertSame(40.0, $result->getFinalPrice());
    }

    public function testGetFallsBackToEnabledChildrenWhenNoChildIsSalable(): void
    {
        $this->inventoryAdapterMock->method('areProductsSalable')->willReturn([
            new InventorySalableResult('child', false),
        ]);

        $result = $this->sut->get(
            $this->createConfigurableMock([$this->createChildMock('child', 20.0, 15.0, Status::STATUS_ENABLED)])
        );

        $this->assertSame(20.0, $result->getRegularPrice());
        $this->assertSame(15.0, $result->getFinalPrice());
    }

    public function testGetFallsBackToEnabledChildrenWhenSalabilityCanNotBeDetermined(): void
    {
        $this->inventoryAdapterMock->method('areProductsSalable')
            ->willThrowException(new LocalizedException(__('Inventory unavailable')));

        $result = $this->sut->get(
            $this->createConfigurableMock([$this->createChildMock('child', 20.0, 15.0, Status::STATUS_ENABLED)])
        );

        $this->assertSame(20.0, $result->getRegularPrice());
        $this->assertSame(15.0, $result->getFinalPrice());
    }

    public function testGetReturnsZeroPricesForConfigurableWithoutEnabledChildren(): void
    {
        $this->inventoryAdapterMock->expects($this->never())->method('areProductsSalable');

        $result = $this->sut->get(
            $this->createConfigurableMock([$this->createChildMock('child', 20.0, 10.0, Status::STATUS_DISABLED)])
        );

        $this->assertSame(0.0, $result->getRegularPrice());
        $this->assertSame(0.0, $result->getFinalPrice());
    }

    /**
     * @param Product[] $children
     * @return Product|MockObject
     */
    private function createConfigurableMock(array $children)
    {
        $parentMock = $this->createMock(Product::class);
        $typeInstanceMock = $this->createMock(Configurable::class);
        $typeInstanceMock->method('getUsedProducts')->with($parentMock)->willReturn($children);
        $parentMock->method('getTypeInstance')->willReturn($typeInstanceMock);
        $parentMock->method('getStoreId')->willReturn((string)self::STORE_ID);
        $parentMock->expects($this->never())->method('getFinalPrice');
        $parentMock->expects($this->never())->method('getPriceInfo');

        return $parentMock;
    }

    /**
     * @return Product|MockObject
     */
    private function createChildMock(string $sku, float $regularPrice, float $finalPrice, int $status)
    {
        $childMock = $this->createMock(Product::class);
        $childMock->method('getSku')->willReturn($sku);
        $childMock->method('getPrice')->willReturn((string)$regularPrice);
        $childMock->method('getFinalPrice')->with(1.0)->willReturn($finalPrice);
        $childMock->method('getStatus')->willReturn((string)$status);

        return $childMock;
    }
}
