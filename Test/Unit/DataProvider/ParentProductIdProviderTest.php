<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\Feed\DataProvider\ParentProductIdProvider;
use RunAsRoot\Feed\Registry\FeedRegistry;

final class ParentProductIdProviderTest extends TestCase
{
    /** @var FeedRegistry|MockObject */
    private FeedRegistry $registry;
    /** @var Configurable|MockObject */
    private Configurable $configurableType;
    private ParentProductIdProvider $sut;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(FeedRegistry::class);
        $this->configurableType = $this->createMock(Configurable::class);
        $this->sut = new ParentProductIdProvider(
            $this->registry,
            $this->configurableType
        );
    }

    public function testGet(): void
    {
        $childProductId = 4571;
        $registryKey = $childProductId . '|parent';

        $this->registry->expects($this->once())
            ->method('registry')
            ->with($registryKey)
            ->willReturn(null);

        $parentProductIds = [ 623, 33451 ];
        $this->configurableType->method('getParentIdsByChild')
            ->with($childProductId)
            ->willReturn($parentProductIds);

        $this->registry->expects($this->once())
            ->method('register')
            ->with($registryKey, reset($parentProductIds));

        $res = $this->sut->get($childProductId);
        $this->assertEquals(reset($parentProductIds), $res);
    }

    public function testGetParentIdNotFound(): void
    {
        $childProductId = 4571;
        $registryKey = $childProductId . '|parent';

        $this->registry->expects($this->once())
            ->method('registry')
            ->with($registryKey)
            ->willReturn(null);

        $this->configurableType->method('getParentIdsByChild')
            ->with($childProductId)
            ->willReturn(null);

        $this->registry->expects($this->once())
            ->method('register')
            ->with($registryKey, null);

        $res = $this->sut->get($childProductId);
        $this->assertEquals(null, $res);
    }

    public function testGetFoundInRegistry(): void
    {
        $parentProductId = 67345;
        $childProductId = 4571;
        $registryKey = $childProductId . '|parent';

        $this->registry->expects($this->exactly(2))
            ->method('registry')
            ->with($registryKey)
            ->willReturn($parentProductId);

        $this->configurableType->expects($this->never())
            ->method('getParentIdsByChild');

        $this->registry->expects($this->never())
            ->method('register');

        $res = $this->sut->get($childProductId);
        $this->assertEquals($parentProductId, $res);
    }
}
