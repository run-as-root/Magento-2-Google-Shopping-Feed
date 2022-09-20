<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Writer;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Phrase;

class FileWriter
{
    private WriteInterface $mediaDirectory;

    private string $destination;

    /**
     * @throws FileSystemException
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function write(string $content): bool
    {
        if (empty($this->destination)) {
            throw new LocalizedException(
                new Phrase('The destination is not set')
            );
        }

        $stream = $this->mediaDirectory->openFile($this->destination, 'w+');
        $stream->write($content);
        $stream->close();
        return true;
    }

    public function setDestination(string $value): self
    {
        $this->destination = $value;
        return $this;
    }
}
