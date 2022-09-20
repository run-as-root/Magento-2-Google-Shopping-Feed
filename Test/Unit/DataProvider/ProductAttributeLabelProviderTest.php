<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\DataProvider;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\DataProvider\ProductAttributeLabelProvider;

final class ProductAttributeLabelProviderTest extends TestCase
{
    private ProductAttributeLabelProvider $sut;

    protected function setUp(): void
    {
        $productResourceMock = $this->createMock(Product::class);
        $attributeMock = $this->createMock(AbstractAttribute::class);
        $attributeFrontendMock = $this->createMock(AbstractFrontend::class);

        $productResourceMock->expects($this->once())
            ->method('getAttribute')
            ->with('attribute_code')
            ->willReturn($attributeMock);

        $attributeMock->expects($this->once())
            ->method('getFrontend')
            ->willReturn($attributeFrontendMock);

        $attributeFrontendMock->expects($this->once())
            ->method('getLabel')
            ->willReturn('Attribute Label');

        $this->sut = new ProductAttributeLabelProvider($productResourceMock);
    }

    public function testGet(): void
    {
        $expected = 'Attribute Label';
        $this->assertEquals($expected, $this->sut->get('attribute_code'));
    }
}
