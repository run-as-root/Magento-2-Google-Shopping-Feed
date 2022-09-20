<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySales\Model\ResourceModel\GetAssignedStockIdForWebsite;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\Service\GetAssignedStockIdForStore;

final class GetAssignedStockIdForStoreTest extends TestCase
{
    /** @var GetAssignedStockIdForWebsite|MockObject */
    private $getAssignedStockIdForWebsite;
    /** @var StoreManagerInterface|MockObject */
    private $storeManager;

    private GetAssignedStockIdForStore $sut;

    protected function setUp(): void
    {
        $this->getAssignedStockIdForWebsite = $this->createMock(GetAssignedStockIdForWebsite::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);

        $this->sut = new GetAssignedStockIdForStore(
            $this->getAssignedStockIdForWebsite,
            $this->storeManager
        );
    }

    public function testLocalizedExceptionIsThrown()
    {
        $storeId = 1;
        $websiteId = 2;

        $store = $this->createMock(StoreInterface::class);
        $store->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($store);
        $this->storeManager->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willThrowException(new LocalizedException(__('Exception')));

        $this->expectException(LocalizedException::class);

        $this->sut->execute($storeId);
    }

    public function testGettingAssignedStockId()
    {
        $storeId = 1;
        $websiteId = 2;
        $websiteCode = 'ch_tectake';
        $stockId = 3;

        $store = $this->createMock(StoreInterface::class);
        $store->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($store);

        $website = $this->createMock(WebsiteInterface::class);
        $website->expects($this->once())
            ->method('getCode')
            ->willReturn($websiteCode);
        $this->storeManager->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($website);

        $this->getAssignedStockIdForWebsite->expects($this->once())
            ->method('execute')
            ->with($websiteCode)
            ->willReturn($stockId);

        $this->sut->execute($storeId);
    }
}
