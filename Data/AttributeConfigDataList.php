<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\Data;

use InvalidArgumentException;

class AttributeConfigDataList
{
    /**
     * @var AttributeConfigData[]
     */
    private array $list;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $list)
    {
        foreach ($list as $item) {
            if (!$item instanceof AttributeConfigData) {
                throw new InvalidArgumentException('Expected item instance of ' . AttributeConfigData::class);
            }
        }

        $this->list = $list;
    }

    /**
     * @return AttributeConfigData[]
     */
    public function getList(): array
    {
        return $this->list;
    }
}
