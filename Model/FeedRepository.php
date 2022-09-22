<?php

namespace RunAsRoot\GoogleShoppingFeed\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use RunAsRoot\GoogleShoppingFeed\Reader\FileReaderProvider;

class FeedRepository implements \RunAsRoot\GoogleShoppingFeed\Api\FeedRepositoryInterface
{
    private FileReaderProvider $fileReaderProvider;

    private FeedFactory $feedFactory;

    public function __construct(
        FileReaderProvider $fileReaderProvider,
        FeedFactory        $feedFactory
    )
    {
        $this->fileReaderProvider = $fileReaderProvider;
        $this->feedFactory = $feedFactory;
    }

    /**
     * @inheritDoc
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getList(): array
    {
        $fileReader = $this->fileReaderProvider->get();

        try {
            $files = $fileReader->read();
        } catch (LocalizedException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        $feeds = [];

        foreach ($files as $file) {
            /** @var Feed $feed */
            $feed = $this->feedFactory->create();

            $feed->setFileName($file['fileName']);
            $feed->setPath($file['path']);
            $feed->setLink($file['link']);
            $feed->setLastGenerated($file['fileGenerationTime']);
            $feed->setStore($file['store']);

            $feeds[] = $feed->toArray();
        }


        return $feeds;
    }
}