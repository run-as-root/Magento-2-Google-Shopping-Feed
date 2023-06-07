<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use RunAsRoot\GoogleShoppingFeed\Api\Data\FeedInterface;

class Feed extends AbstractExtensibleModel implements FeedInterface
{
    public function getFileName(): string
    {
        return $this->getData(self::FILENAME);
    }

    public function setFileName(string $fileName): FeedInterface
    {
        return $this->setData(self::FILENAME, $fileName);
    }

    public function getPath(): string
    {
        return $this->getData(self::PATH);
    }

    public function setPath(string $path): FeedInterface
    {
        return $this->setData(self::PATH, $path);
    }

    public function getLink(): string
    {
        return $this->getData(self::LINK);
    }

    public function setLink(string $link): FeedInterface
    {
        return $this->setData(self::LINK, $link);
    }

    public function getLastGenerated(): string
    {
        return $this->getData(self::LAST_GENERATED);
    }

    public function setLastGenerated(string $lastGenerated): FeedInterface
    {
        return $this->setData(self::LAST_GENERATED, $lastGenerated);
    }

    public function getStore(): string
    {
        return $this->getData(self::STORE);
    }

    public function setStore(string $store): FeedInterface
    {
        return $this->setData(self::STORE, $store);
    }
}
