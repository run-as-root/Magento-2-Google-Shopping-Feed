<?php

namespace RunAsRoot\GoogleShoppingFeed\Reader;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use FilesystemIterator;
use FilesystemIteratorFactory;
use DateTime;


class FileReader
{
    private UrlInterface $urlBuilder;

    private FilesystemIteratorFactory $filesystemIteratorFactory;

    private DateTime $dateTime;

    private string $destination;

    public function __construct(
        UrlInterface              $urlBuilder,
        FilesystemIteratorFactory $filesystemIteratorFactory,
        DateTime                  $dateTime
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->filesystemIteratorFactory = $filesystemIteratorFactory;
        $this->dateTime = $dateTime;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function read(): array
    {
        if (empty($this->destination)) {
            throw new LocalizedException(
                new Phrase('The destination is not set')
            );
        }

        try {
            $dir = $this->filesystemIteratorFactory->create([
                'path' => DirectoryList::MEDIA . DIRECTORY_SEPARATOR . $this->destination,
                'flags' => FilesystemIterator::SKIP_DOTS
            ]);
        } catch (\UnexpectedValueException $exception) {
            return [];
        }

        $result = [];
        $storeMediaUrl = $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]);

        while ($dir->valid()) {
            if (!$dir->isDir()) {
                $this->dateTime->setTimestamp($dir->getMTime());
                $fileGenerationTime = $this->dateTime->format('Y-m-d H:i:s');
                $fileName = $dir->getFilename();
                $stores = null;
                preg_match('/_store_(\w+)_feed/', $fileName, $stores);
                $result[] = [
                    'path' => $dir->getPath() . DIRECTORY_SEPARATOR . $fileName,
                    'fileGenerationTime' => $fileGenerationTime,
                    'link' => $storeMediaUrl . str_replace('media/', '', $dir->getPath())
                        . DIRECTORY_SEPARATOR . $fileName,
                    'fileName' => $fileName,
                    'store' => $stores[1] ?? 'default'
                ];
            }

            $dir->next();
        }

        return $result;
    }

    public function setDestination(string $value): FileReader
    {
        $this->destination = $value;
        return $this;
    }
}