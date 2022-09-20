<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\ItemGroupIdProvider;
use RunAsRoot\Feed\DataProvider\ParentProductProvider;

final class ItemGroupIdProviderTest extends TestCase
{
    private ItemGroupIdProvider $sut;

    /** @var ParentProductProvider|MockObject */
    private $parentProductProvider;

    protected function setUp(): void
    {
        $this->parentProductProvider = $this->createMock(ParentProductProvider::class);
        $this->sut = new ItemGroupIdProvider($this->parentProductProvider);
    }

    public function testGet(): void
    {
        $productMock = $this->createMock(Product::class);
        $parentProductMock = $this->createMock(Product::class);

        $this->parentProductProvider->expects($this->once())
            ->method('get')
            ->with($productMock)
            ->willReturn($parentProductMock);

        $parentProductMock->expects($this->once())
            ->method('getSku')
            ->willReturn('some-sku');

        $this->assertEquals('some-sku', $this->sut->get($productMock));
    }
}
