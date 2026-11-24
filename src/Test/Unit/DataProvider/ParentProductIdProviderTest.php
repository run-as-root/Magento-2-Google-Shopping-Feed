<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider\AttributeHandlers;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\CollectionProvider\ExportableConfigurableParentsCollectionProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\ParentProductIdProvider;
use RunAsRoot\GoogleShoppingFeed\Registry\FeedRegistry;

final class ParentProductIdProviderTest extends TestCase
{
    /** @var FeedRegistry|MockObject */
    private FeedRegistry $registry;
    /** @var Configurable|MockObject */
    private Configurable $configurableType;
    /** @var ExportableConfigurableParentsCollectionProvider|MockObject */
    private ExportableConfigurableParentsCollectionProvider $exportableParentsCollectionProvider;
    private ParentProductIdProvider $sut;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(FeedRegistry::class);
        $this->configurableType = $this->createMock(Configurable::class);
        $this->exportableParentsCollectionProvider = $this->createMock(
            ExportableConfigurableParentsCollectionProvider::class
        );
        $this->sut = new ParentProductIdProvider(
            $this->registry,
            $this->configurableType,
            $this->exportableParentsCollectionProvider
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

    public function testGetForStoreReturnsLowestExportableParentId(): void
    {
        $childProductId = 4571;
        $storeId = 2;
        $registryKey = $childProductId . '|exportable_parents';

        $this->registry->method('registryForStore')->with($registryKey, $storeId)->willReturn(null);
        $this->configurableType->method('getParentIdsByChild')
            ->with($childProductId)
            ->willReturn([ 623, 33451, 700 ]);

        $exportableParentsCollection = $this->createMock(ProductCollection::class);
        $exportableParentsCollection->expects($this->once())
            ->method('addIdFilter')
            ->with([ 623, 33451, 700 ])
            ->willReturnSelf();
        $exportableParentsCollection->method('getAllIds')->willReturn([ '33451', '700' ]);
        $this->exportableParentsCollectionProvider->expects($this->once())
            ->method('get')
            ->with($storeId)
            ->willReturn($exportableParentsCollection);

        $this->registry->expects($this->once())
            ->method('registerForStore')
            ->with($registryKey, $storeId, [ 700, 33451 ]);

        $this->assertSame(700, $this->sut->get($childProductId, $storeId));
    }

    public function testGetForStoreReturnsNullWhenNoParentIsExportable(): void
    {
        $this->registry->method('registryForStore')->willReturn(null);
        $this->configurableType->method('getParentIdsByChild')->willReturn([ 623 ]);

        $exportableParentsCollection = $this->createMock(ProductCollection::class);
        $exportableParentsCollection->method('addIdFilter')->willReturnSelf();
        $exportableParentsCollection->method('getAllIds')->willReturn([]);
        $this->exportableParentsCollectionProvider->method('get')->willReturn($exportableParentsCollection);

        $this->registry->expects($this->once())
            ->method('registerForStore')
            ->with('4571|exportable_parents', 2, []);

        $this->assertNull($this->sut->get(4571, 2));
    }

    public function testGetForStoreSkipsExportabilityCheckForProductsWithoutParents(): void
    {
        $this->registry->method('registryForStore')->willReturn(null);
        $this->configurableType->method('getParentIdsByChild')->willReturn([]);
        $this->exportableParentsCollectionProvider->expects($this->never())->method('get');

        $this->assertNull($this->sut->get(4571, 2));
    }

    public function testGetForStoreUsesRegistry(): void
    {
        $this->registry->method('registryForStore')->with('4571|exportable_parents', 2)->willReturn([]);
        $this->configurableType->expects($this->never())->method('getParentIdsByChild');
        $this->registry->expects($this->never())->method('registerForStore');

        $this->assertNull($this->sut->get(4571, 2));
    }
}
