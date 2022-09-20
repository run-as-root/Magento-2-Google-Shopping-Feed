<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\DataProvider;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use RunAsRoot\Feed\Registry\FeedRegistry;

class ParentProductIdProvider
{
    private FeedRegistry $registry;
    private Configurable $configurableType;

    public function __construct(FeedRegistry $registry, Configurable $configurableType)
    {
        $this->registry = $registry;
        $this->configurableType = $configurableType;
    }

    public function get(int $childProductId): ?int
    {
        $registryKey = $childProductId . '|parent';

        if ($this->registry->registry($registryKey)) {
            return $this->registry->registry($registryKey);
        }

        $parentIds = $this->configurableType->getParentIdsByChild($childProductId);
        $parentId = null;

        if (isset($parentIds[0])) {
            $parentId = (int)$parentIds[0];
        }

        $this->registry->register($registryKey, $parentId);
        return $parentId;
    }
}
