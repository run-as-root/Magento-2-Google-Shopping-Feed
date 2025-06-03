<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\Reader;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;
use RunAsRoot\GoogleShoppingFeed\Reader\FileReader;
use PHPUnit\Framework\TestCase;
use DateTime;

final class FileReaderTest extends TestCase
{
    private UrlInterface $urlBuilderMock;

    private DateTime $dateTimeMock;

    private FileReader $sut;

    private $dirFactoryMock;

    private $dirMock;

    private const DESTINATION = 'run_as_root/feed/';

    protected function setUp(): void
    {
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)->getMock();
        $this->dirFactoryMock = $this->createMock(\FilesystemIteratorFactory::class);
        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)->getMock();

        $this->dirMock = $this->createMock(\FilesystemIterator::class);

        $this->dirFactoryMock->method('create')->with([
            'directory' => DirectoryList::MEDIA . DIRECTORY_SEPARATOR . self::DESTINATION,
            'flags' => \FilesystemIterator::SKIP_DOTS
        ])->willReturn($this->dirMock);

        $this->sut = new FileReader($this->urlBuilderMock, $this->dirFactoryMock, $this->dateTimeMock);
        $this->sut->setDestination(self::DESTINATION);
    }

    public function testDirDoesntExists(): void
    {
        $this->dirFactoryMock->method('create')->with([
            'directory' => DirectoryList::MEDIA . DIRECTORY_SEPARATOR . self::DESTINATION,
            'flags' => \FilesystemIterator::SKIP_DOTS
        ])->willThrowException(new \UnexpectedValueException);

        $this->assertEquals([], $this->sut->read());
    }

    public function testReadWithoutFiles(): void
    {

        $this->dirMock->method('valid')->willReturnOnConsecutiveCalls(true, true, false);

        $storeMediaUrl = 'https://local.magento2.com/media';
        $this->urlBuilderMock->method('getBaseUrl')
            ->with(['_type' => UrlInterface::URL_TYPE_MEDIA])
            ->willReturn($storeMediaUrl);

        $this->dirMock->method('isDir')->willReturn(true);

        $this->assertEquals([], $this->sut->read());
    }

    public function testRead(): void
    {
        $this->markTestSkipped('must be revisited.');

        $this->dirMock->method('valid')->willReturnOnConsecutiveCalls(true, true, false);

        $storeMediaUrl = 'https://local.magento2.com/media';
        $this->urlBuilderMock->method('getBaseUrl')
            ->with(['_type' => UrlInterface::URL_TYPE_MEDIA])
            ->willReturn($storeMediaUrl);

        $this->dirMock->method('isDir')->willReturn(false);

        $fileGenerationTime = '2022-09-21 15:10:58';
        $filename = 'base_store_default_feed_1.xml';
        $filePath = self::DESTINATION;
        $fileMTime = 1663686469;

        $this->dirMock->method('getMTime')->willReturn($fileMTime);
        $this->dateTimeMock->method('setTimestamp')
            ->with($fileMTime)
            ->willReturn($fileMTime);
        $this->dateTimeMock->method('format')
            ->with('Y-m-d H:i:s')
            ->willReturn($fileGenerationTime);
        $this->dirMock->method('getFilename')->willReturn($filename);
        $this->dirMock->method('getPath')->willReturn($filePath);

        $resultItem = current($this->sut->read());

        $this->assertArrayHasKey('link', $resultItem);
        $this->assertArrayHasKey('fileGenerationTime', $resultItem);
    }
}
