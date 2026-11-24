<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\Price;
use Magento\Catalog\Model\Product\Type\Simple;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Api\Data\GroupInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\DataProvider\SingleItemGuestCartPriceProvider;

final class SingleItemGuestCartPriceProviderTest extends TestCase
{
    private SingleItemGuestCartPriceProvider $sut;

    /** @var Price|MockObject */
    private $priceModelMock;

    /** @var array<array{sku: string, qty: mixed, customer_group_id: mixed}> */
    private array $priceModelCalls = [];

    /** @var array<string, float> */
    private array $guestPricesBySku = [];

    protected function setUp(): void
    {
        $this->sut = new SingleItemGuestCartPriceProvider();
        $this->priceModelMock = $this->createMock(Price::class);
        $this->priceModelMock->method('getFinalPrice')->willReturnCallback(
            function ($qty, Product $product): float {
                $this->priceModelCalls[] = [
                    'sku' => $product->getData('sku'),
                    'qty' => $qty,
                    'customer_group_id' => $product->getData('customer_group_id'),
                ];
                $guestPrice = $this->guestPricesBySku[$product->getData('sku')];
                $product->setData('final_price', $guestPrice);

                return $guestPrice;
            }
        );
    }

    public function testGetReturnsGuestPriceOfOneItemWithoutChangingTheProduct(): void
    {
        $this->guestPricesBySku = [ 'MP03-32-Red' => 40.8 ];
        $productMock = $this->createProduct('MP03-32-Red', $this->createMock(Simple::class));
        $productMock->setData('final_price', 51.0);
        $productDataBefore = $productMock->getData();

        $this->assertSame(40.8, $this->sut->get($productMock));
        $this->assertSame(
            [ [ 'sku' => 'MP03-32-Red', 'qty' => 1.0, 'customer_group_id' => GroupInterface::NOT_LOGGED_IN_ID ] ],
            $this->priceModelCalls
        );
        $this->assertSame($productDataBefore, $productMock->getData());
        $this->assertSame(51.0, $productMock->getData('final_price'));
    }

    public function testGetReturnsLowestEnabledChildPriceForConfigurableProduct(): void
    {
        $this->guestPricesBySku = [ 'MP03-32-Red' => 40.8, 'MP03-33-Red' => 30.0, 'MP03-34-Red' => 45.0 ];
        $typeInstanceMock = $this->createMock(Configurable::class);
        $productMock = $this->createProduct('MP03', $typeInstanceMock);
        $children = [
            $this->createChild('MP03-32-Red', 1),
            $this->createChild('MP03-33-Red', 2),
            $this->createChild('MP03-34-Red', 1),
        ];
        $typeInstanceMock->method('getUsedProducts')->with($productMock)->willReturn($children);
        $childDataBefore = $children[0]->getData();

        $this->assertSame(40.8, $this->sut->get($productMock));
        $this->assertSame($childDataBefore, $children[0]->getData());
    }

    public function testGetReturnsOwnPriceForConfigurableProductWithoutEnabledChildren(): void
    {
        $this->guestPricesBySku = [ 'MP03' => 52.0 ];
        $typeInstanceMock = $this->createMock(Configurable::class);
        $productMock = $this->createProduct('MP03', $typeInstanceMock);
        $typeInstanceMock->method('getUsedProducts')->willReturn([ $this->createChild('MP03-32-Red', 2) ]);

        $this->assertSame(52.0, $this->sut->get($productMock));
    }

    /**
     * @param object $typeInstance
     * @return Product|MockObject
     */
    private function createProduct(string $sku, $typeInstance)
    {
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'getPriceModel', 'getTypeInstance' ])
            ->getMock();
        $productMock->setData([ 'sku' => $sku, 'status' => 1 ]);
        $productMock->method('getPriceModel')->willReturn($this->priceModelMock);
        $productMock->method('getTypeInstance')->willReturn($typeInstance);

        return $productMock;
    }

    /**
     * @return Product|MockObject
     */
    private function createChild(string $sku, int $status)
    {
        $childMock = $this->createProduct($sku, $this->createMock(Simple::class));
        $childMock->setData('status', $status);

        return $childMock;
    }
}
