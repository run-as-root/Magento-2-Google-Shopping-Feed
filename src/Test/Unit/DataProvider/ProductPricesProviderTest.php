<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type\Simple;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Api\Data\GroupInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ProductPricesProvider;

final class ProductPricesProviderTest extends TestCase
{
    private ProductPricesProvider $sut;

    protected function setUp(): void
    {
        $this->sut = new ProductPricesProvider();
    }

    public function testGetReturnsOwnPricesForNotLoggedInCustomerGroup(): void
    {
        $productMock = $this->createProductMock(34.0);
        $productMock->method('getTypeInstance')->willReturn($this->createMock(Simple::class));
        $productMock->expects($this->never())->method('getPriceInfo');

        $calls = [];
        $productMock->method('setData')
            ->willReturnCallback(static function ($key, $value) use (&$calls, $productMock) {
                $calls[] = $key . ':' . $value;
                return $productMock;
            });
        $productMock->method('getFinalPrice')
            ->willReturnCallback(static function () use (&$calls) {
                $calls[] = 'getFinalPrice';
                return 29.0;
            });

        $result = $this->sut->get($productMock);

        $this->assertSame(34.0, $result->getRegularPrice());
        $this->assertSame(29.0, $result->getFinalPrice());
        $this->assertSame(
            ['customer_group_id:' . GroupInterface::NOT_LOGGED_IN_ID, 'getFinalPrice'],
            $calls
        );
    }

    public function testGetReturnsPricesOfCheapestAvailableChildForConfigurable(): void
    {
        $cheapestChild = $this->createChildMock(60.0, 45.0, Status::STATUS_ENABLED, true);
        $otherChild = $this->createChildMock(50.0, 50.0, Status::STATUS_ENABLED, true);
        $disabledChild = $this->createChildMock(20.0, 10.0, Status::STATUS_DISABLED, true);
        $outOfStockChild = $this->createChildMock(20.0, 15.0, Status::STATUS_ENABLED, false);

        $parentMock = $this->createConfigurableMock([$otherChild, $disabledChild, $cheapestChild, $outOfStockChild]);

        $result = $this->sut->get($parentMock);

        $this->assertSame(60.0, $result->getRegularPrice());
        $this->assertSame(45.0, $result->getFinalPrice());
    }

    public function testGetReturnsZeroPricesForConfigurableWithoutAvailableChildren(): void
    {
        $parentMock = $this->createConfigurableMock(
            [$this->createChildMock(20.0, 10.0, Status::STATUS_DISABLED, true)]
        );

        $result = $this->sut->get($parentMock);

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
        $parentMock->expects($this->never())->method('getFinalPrice');
        $parentMock->expects($this->never())->method('getPriceInfo');

        return $parentMock;
    }

    /**
     * @return Product|MockObject
     */
    private function createChildMock(float $regularPrice, float $finalPrice, int $status, bool $isInStock)
    {
        $childMock = $this->createProductMock($regularPrice);
        $childMock->method('getStatus')->willReturn((string)$status);
        $childMock->method('isInStock')->willReturn($isInStock);
        $childMock->method('getFinalPrice')->willReturn($finalPrice);

        return $childMock;
    }

    /**
     * @return Product|MockObject
     */
    private function createProductMock(float $regularPrice)
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getPrice')->willReturn((string)$regularPrice);

        return $productMock;
    }
}
