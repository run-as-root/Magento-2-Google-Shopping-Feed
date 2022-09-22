<?php

namespace RunAsRoot\GoogleShoppingFeed\Model;

use RunAsRoot\GoogleShoppingFeed\Api\Data\FeedInterface;

class Feed extends \Magento\Framework\Model\AbstractExtensibleModel
    implements \RunAsRoot\GoogleShoppingFeed\Api\Data\FeedInterface
{

    /**
     * @inheritDoc
     */
    public function getFileName(): string
    {
        return $this->getData(self::FILENAME);
    }

    /**
     * @inheritDoc
     */
    public function setFileName(string $fileName): FeedInterface
    {
        return $this->setData(self::FILENAME, $fileName);
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return $this->getData(self::PATH);
    }

    /**
     * @inheritDoc
     */
    public function setPath(string $path): FeedInterface
    {
        return $this->setData(self::PATH, $path);
    }

    /**
     * @inheritDoc
     */
    public function getLink(): string
    {
        return $this->getData(self::LINK);
    }

    /**
     * @inheritDoc
     */
    public function setLink(string $link): FeedInterface
    {
        return $this->setData(self::LINK, $link);
    }

    /**
     * @inheritDoc
     */
    public function getLastGenerated(): string
    {
        return $this->getData(self::LAST_GENERATED);
    }

    /**
     * @inheritDoc
     */
    public function setLastGenerated(string $lastGenerated): FeedInterface
    {
        return $this->setData(self::LAST_GENERATED, $lastGenerated);
    }

    /**
     * @inheritDoc
     */
    public function getStore(): string
    {
        return $this->getData(self::STORE);
    }

    /**
     * @inheritDoc
     */
    public function setStore(string $store): FeedInterface
    {
        return $this->setData(self::STORE, $store);
    }
}