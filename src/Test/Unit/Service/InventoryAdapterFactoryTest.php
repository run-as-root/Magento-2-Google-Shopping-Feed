<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\Service;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\Exception\InventorySystemUnavailableException;
use RunAsRoot\GoogleShoppingFeed\Service\InventoryAdapterFactory;
use RunAsRoot\GoogleShoppingFeed\Service\LegacyInventoryAdapter;
use RunAsRoot\GoogleShoppingFeed\Service\MsiInventoryAdapter;

class InventoryAdapterFactoryTest extends TestCase
{
    /** @var ComponentRegistrar|MockObject */
    private $componentRegistrarMock;

    /** @var ObjectManagerInterface|MockObject */
    private $objectManagerMock;

    /** @var StoreManagerInterface|MockObject */
    private $storeManagerMock;

    /** @var StockRegistryInterface|MockObject */
    private $stockRegistryMock;

    private InventoryAdapterFactory $inventoryAdapterFactory;

    protected function setUp(): void
    {
        $this->componentRegistrarMock = $this->createMock(ComponentRegistrar::class);
        $this->objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->stockRegistryMock = $this->createMock(StockRegistryInterface::class);

        $this->inventoryAdapterFactory = new InventoryAdapterFactory(
            $this->componentRegistrarMock,
            $this->objectManagerMock,
            $this->storeManagerMock,
            $this->stockRegistryMock
        );
    }

    public function testCreateReturnsLegacyAdapterWhenMsiNotAvailable(): void
    {
        // In this environment MSI classes don't exist, so it should return legacy adapter
        $result = $this->inventoryAdapterFactory->create();

        $this->assertInstanceOf(LegacyInventoryAdapter::class, $result);
    }

    public function testCreateThrowsExceptionWhenNoInventorySystemAvailable(): void
    {
        // Create a partial mock to override the legacy inventory check
        $factory = $this->getMockBuilder(InventoryAdapterFactory::class)
            ->setConstructorArgs([
                $this->componentRegistrarMock,
                $this->objectManagerMock,
                $this->storeManagerMock,
                $this->stockRegistryMock
            ])
            ->onlyMethods(['isLegacyInventoryAvailable'])
            ->getMock();

        $factory->expects($this->once())
            ->method('isLegacyInventoryAvailable')
            ->willReturn(false);

        $this->expectException(InventorySystemUnavailableException::class);
        $this->expectExceptionMessage('Neither MSI nor legacy inventory system is available.');

        $factory->create();
    }

    public function testCreateReturnsLegacyAdapterWhenMsiClassesDoNotExist(): void
    {
        // Since MSI classes don't actually exist in this test environment, 
        // it should fall back to legacy adapter
        $result = $this->inventoryAdapterFactory->create();

        $this->assertInstanceOf(LegacyInventoryAdapter::class, $result);
    }
}