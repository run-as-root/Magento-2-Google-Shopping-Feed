<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Writer;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

use function sprintf;

class XmlFileWriterProvider
{
    public const DIRECTORY_PATH = 'run_as_root/feed';
    public const FILE_NAME_PATTERN = '%s_store_%s_feed.xml';

    private FileWriterFactory $fileWriterFactory;
    private WebsiteRepositoryInterface $websiteRepository;

    public function __construct(
        FileWriterFactory $fileWriterFactory,
        WebsiteRepositoryInterface $websiteRepository
    ) {
        $this->fileWriterFactory = $fileWriterFactory;
        $this->websiteRepository = $websiteRepository;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function get(StoreInterface $store): FileWriter
    {
        $website = $this->websiteRepository->getById($store->getWebsiteId());
        $fileName = sprintf(self::FILE_NAME_PATTERN, $website->getCode(), $store->getCode());
        $destination = self::DIRECTORY_PATH . DIRECTORY_SEPARATOR . $fileName;

        $fileWriter = $this->fileWriterFactory->create();
        $fileWriter->setDestination($destination);

        return $fileWriter;
    }
}
