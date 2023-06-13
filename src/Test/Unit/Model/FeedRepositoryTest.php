<?php

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\Model\FeedFactory;
use RunAsRoot\GoogleShoppingFeed\Model\FeedRepository;
use RunAsRoot\GoogleShoppingFeed\Reader\FileReader;
use RunAsRoot\GoogleShoppingFeed\Reader\FileReaderProvider;
use RunAsRoot\GoogleShoppingFeed\Model\Feed;


class FeedRepositoryTest extends TestCase
{
    private FeedRepository $sut;

    private FileReaderProvider $fileReaderProviderMock;

    private FeedFactory $feedFactoryMock;

    protected function setUp(): void
    {
        $this->fileReaderProviderMock = $this->createMock(FileReaderProvider::class);
        $this->feedFactoryMock = $this->createMock(FeedFactory::class);

        $this->sut = new FeedRepository($this->fileReaderProviderMock, $this->feedFactoryMock);
    }

    public function testLocalizedExceptionThrown(): void
    {
        $fileReaderMock = $this->createMock(FileReader::class);
        $this->fileReaderProviderMock->method('get')->willReturn($fileReaderMock);

        $expectedLogMessage = 'The destination is not set';

        $this->fileReaderProviderMock->expects($this->once())
            ->method('get')
            ->willThrowException(new LocalizedException(__($expectedLogMessage)));

        $this->expectExceptionMessage($expectedLogMessage);

        $this->sut->getList();
    }

    public function testGetListEmptyResult(): void
    {
        $fileReaderMock = $this->createMock(FileReader::class);
        $this->fileReaderProviderMock->method('get')->willReturn($fileReaderMock);

        $fileReaderMock->method('read')->willReturn([]);

        $this->assertEquals([], $this->sut->getList());
    }

    public function testGetList(): void
    {
        $fileReaderMock = $this->createMock(FileReader::class);
        $this->fileReaderProviderMock->method('get')->willReturn($fileReaderMock);

        $file = [
            'fileName' => 'base_store_default_feed.xml',
            'path' => 'media/run_as_root/feed/base_store_default_feed.xml',
            'link' => 'https://local.magento2.com/media/run_as_root/feed/base_store_default_feed.xml',
            'fileGenerationTime' => date('Y-m-d H:i:s'),
            'store' => 'default'
        ];

        $files = [
            $file
        ];

        $fileReaderMock->method('read')->willReturn($files);

        $feedMock = $this->createMock(Feed::class);
        $this->feedFactoryMock->method('create')->willReturn($feedMock);

        $feedMock->method('setFileName')->with($file['fileName'])->willReturn($feedMock);
        $feedMock->method('setPath')->with($file['path'])->willReturn($feedMock);
        $feedMock->method('setLink')->with($file['link'])->willReturn($feedMock);
        $feedMock->method('setLastGenerated')->with($file['fileGenerationTime'])->willReturn($feedMock);
        $feedMock->method('setStore')->with($file['store'])->willReturn($feedMock);

        $feedMock->method('toArray')->willReturn([
            'filename' => $file['fileName'],
            'path' => $file['path'],
            'link' => $file['link'],
            'last_generated' => $file['fileGenerationTime'],
            'store' => $file['store']
        ]);

        $this->assertArrayHasKey('link', $this->sut->getList()[0]);
    }
}
