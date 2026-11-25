<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ParentProductIdProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ParentProductProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ProductProvider;
use RunAsRoot\GoogleShoppingFeed\Registry\FeedRegistry;

final class ParentProductProviderTest extends TestCase
{
    /** @var ProductProvider|MockObject */
    private $productProvider;

    /** @var ParentProductIdProvider|MockObject */
    private $parentProductIdProvider;

    private ParentProductProvider $sut;

    protected function setUp(): void
    {
        $this->productProvider = $this->createMock(ProductProvider::class);
        $this->parentProductIdProvider = $this->createMock(ParentProductIdProvider::class);

        $this->sut = new ParentProductProvider(
            new FeedRegistry(),
            $this->productProvider,
            $this->parentProductIdProvider
        );
    }

    public function testReturnsExportableParentOfTheProductInItsStore(): void
    {
        $childProduct = $this->createProduct(5, 2, Type::TYPE_SIMPLE);
        $parentProduct = $this->createMock(Product::class);

        $this->parentProductIdProvider->expects($this->once())
            ->method('get')
            ->with(5, 2)
            ->willReturn(62);
        $this->productProvider->expects($this->once())
            ->method('get')
            ->with(62, 2)
            ->willReturn($parentProduct);

        $this->assertSame($parentProduct, $this->sut->get($childProduct));
    }

    public function testReturnsParentOfVirtualConfigurableChild(): void
    {
        $childProduct = $this->createProduct(5, 2, Type::TYPE_VIRTUAL);
        $parentProduct = $this->createMock(Product::class);

        $this->parentProductIdProvider->method('get')->with(5, 2)->willReturn(62);
        $this->productProvider->method('get')->with(62, 2)->willReturn($parentProduct);

        $this->assertSame($parentProduct, $this->sut->get($childProduct));
    }

    public function testReturnsTheProductItselfWithoutExportableParent(): void
    {
        $product = $this->createProduct(5, 2, Type::TYPE_SIMPLE);

        $this->parentProductIdProvider->method('get')->with(5, 2)->willReturn(null);
        $this->productProvider->expects($this->never())->method('get');

        $this->assertSame($product, $this->sut->get($product));
    }

    public function testPrefersTheParentTheVariantIsExportedWith(): void
    {
        $childProduct = $this->createProduct(5, 2, Type::TYPE_SIMPLE, 70);
        $parentProduct = $this->createMock(Product::class);

        $this->parentProductIdProvider->expects($this->never())->method('get');
        $this->productProvider->expects($this->once())
            ->method('get')
            ->with(70, 2)
            ->willReturn($parentProduct);

        $this->assertSame($parentProduct, $this->sut->get($childProduct));
    }

    private function createProduct(int $id, int $storeId, string $typeId, ?int $variantParentId = null): Product
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn($id);
        $product->method('getStoreId')->willReturn($storeId);
        $product->method('getTypeId')->willReturn($typeId);
        $product->method('getData')
            ->with(ParentProductProvider::VARIANT_PARENT_ID)
            ->willReturn($variantParentId);

        return $product;
    }
}
