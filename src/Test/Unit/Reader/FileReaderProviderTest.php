<?php

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\Reader;

use RunAsRoot\GoogleShoppingFeed\Reader\FileReader;
use RunAsRoot\GoogleShoppingFeed\Reader\FileReaderFactory;
use RunAsRoot\GoogleShoppingFeed\Reader\FileReaderProvider;
use PHPUnit\Framework\TestCase;

class FileReaderProviderTest extends TestCase
{
    private FileReaderFactory $fileReaderFactoryMock;

    private FileReaderProvider $sut;

    protected function setUp(): void
    {
        $this->fileReaderFactoryMock = $this->createMock(FileReaderFactory::class);

        $this->sut = new FileReaderProvider($this->fileReaderFactoryMock);
    }

    public function testGet()
    {
        $fileReaderMock = $this->createMock(FileReader::class);
        $destination = 'run_as_root/feed';

        $this->fileReaderFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($fileReaderMock);

        $fileReaderMock->expects($this->once())
            ->method('setDestination')
            ->with($destination)
            ->willReturnSelf();

        $this->sut->get();
    }
}
