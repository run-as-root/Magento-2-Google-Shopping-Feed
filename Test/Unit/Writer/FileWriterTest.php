<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\File\WriteInterface as FileWriteInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\Writer\FileWriter;

final class FileWriterTest extends TestCase
{
    private FileWriter $sut;

    /**
     * @var WriteInterface|MockObject
     */
    private $mediaDirectoryMock;

    protected function setUp(): void
    {
        $fileSystemMock = $this->createMock(Filesystem::class);
        $this->mediaDirectoryMock = $this->getMockBuilder(WriteInterface::class)->getMock();

        $fileSystemMock->expects($this->once())
            ->method('getDirectoryWrite')
            ->with('media')
            ->willReturn($this->mediaDirectoryMock);

        $this->sut = new FileWriter($fileSystemMock);
    }

    public function testWrite(): void
    {
        $streamMock = $this->getMockBuilder(FileWriteInterface::class)->getMock();

        $destination = 'media/run_as_root/feed/base_store_seidenland_de_feed.xml';
        $this->sut->setDestination($destination);

        $this->mediaDirectoryMock
            ->expects($this->once())
            ->method('openFile')
            ->with($destination, 'w+')
            ->willReturn($streamMock);

        $content = '<?xml version="1.0"?><rss><channel/></rss>';

        $streamMock->expects($this->once())->method('write')->with($content);
        $streamMock->expects($this->once())->method('close');

        $this->assertTrue($this->sut->write($content));
    }
}
