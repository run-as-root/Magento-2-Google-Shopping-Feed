<?php

namespace RunAsRoot\GoogleShoppingFeed\Reader;

use RunAsRoot\GoogleShoppingFeed\Writer\XmlFileWriterProvider;

class FileReaderProvider
{
    private FileReaderFactory $fileReaderFactory;

    public function __construct(
        FileReaderFactory $fileReaderFactory
    ) {
        $this->fileReaderFactory = $fileReaderFactory;
    }

    public function get(): FileReader
    {
        $fileReader = $this->fileReaderFactory->create();
        $fileReader->setDestination(XmlFileWriterProvider::DIRECTORY_PATH);

        return $fileReader;
    }
}