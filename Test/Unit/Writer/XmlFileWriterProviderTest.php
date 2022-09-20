<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\Writer\FileWriter;
use RunAsRoot\GoogleShoppingFeed\Writer\XmlFileWriterProvider;
use RunAsRoot\GoogleShoppingFeed\Writer\FileWriterFactory;

final class XmlFileWriterProviderTest extends TestCase
{
    private XmlFileWriterProvider $sut;

    /**
     * @var FileWriterFactory|MockObject
     */
    private $fileWriterFactoryMock;

    /**
     * @var WebsiteRepositoryInterface|MockObject
     */
    private $websiteRepositoryMock;

    protected function setUp(): void
    {
        $this->fileWriterFactoryMock = $this->createMock(FileWriterFactory::class);
        $this->websiteRepositoryMock = $this->getMockBuilder(WebsiteRepositoryInterface::class)->getMock();
        $this->sut = new XmlFileWriterProvider($this->fileWriterFactoryMock, $this->websiteRepositoryMock);
    }

    public function testGet1(): void
    {
        $storeMock = $this->getMockBuilder(StoreInterface::class)->getMock();
        $websiteMock = $this->getMockBuilder(WebsiteInterface::class)->getMock();
        $fileWriterMock = $this->createMock(FileWriter::class);

        $storeId = 100;
        $storeMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($storeId);

        $this->websiteRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($storeId)
            ->willReturn($websiteMock);

        $storeCode = 'seidenland_de';
        $storeMock->expects($this->once())
            ->method('getCode')
            ->willReturn($storeCode);

        $websiteCode = 'base';
        $websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn($websiteCode);

        $this->fileWriterFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($fileWriterMock);

        $fileWriterMock->expects($this->once())
            ->method('setDestination')
            ->with('run_as_root/feed/base_store_seidenland_de_feed.xml')
            ->willReturnSelf();

        $this->sut->get($storeMock);
    }
}
