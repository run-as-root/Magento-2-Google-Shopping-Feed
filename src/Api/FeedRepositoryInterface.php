<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Api;

use RunAsRoot\GoogleShoppingFeed\Api\Data\FeedInterface;

interface FeedRepositoryInterface
{
    /**
     * Retrieve list of feeds
     *
     * @return FeedInterface[]
     */
    public function getList(): array;
}
