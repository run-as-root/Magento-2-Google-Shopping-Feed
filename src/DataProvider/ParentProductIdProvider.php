<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use RunAsRoot\GoogleShoppingFeed\CollectionProvider\ExportableConfigurableParentsCollectionProvider;
use RunAsRoot\GoogleShoppingFeed\Registry\FeedRegistry;

class ParentProductIdProvider
{
    private FeedRegistry $registry;
    private Configurable $configurableType;
    private ExportableConfigurableParentsCollectionProvider $exportableConfigurableParentsCollectionProvider;

    public function __construct(
        FeedRegistry $registry,
        Configurable $configurableType,
        ExportableConfigurableParentsCollectionProvider $exportableConfigurableParentsCollectionProvider
    ) {
        $this->registry = $registry;
        $this->configurableType = $configurableType;
        $this->exportableConfigurableParentsCollectionProvider = $exportableConfigurableParentsCollectionProvider;
    }

    public function get(int $childProductId, ?int $storeId = null): ?int
    {
        if ($storeId === null) {
            return $this->getFirstParentId($childProductId);
        }

        $registryKey = $childProductId . '|exportable_parents';
        $exportableParentIds = $this->registry->registryForStore($registryKey, $storeId);

        if (!is_array($exportableParentIds)) {
            $exportableParentIds = $this->getExportableParentIds($childProductId, $storeId);
            $this->registry->registerForStore($registryKey, $storeId, $exportableParentIds);
        }

        return $exportableParentIds[0] ?? null;
    }

    private function getFirstParentId(int $childProductId): ?int
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

    /**
     * @return array<int>
     */
    private function getExportableParentIds(int $childProductId, int $storeId): array
    {
        $parentIds = $this->configurableType->getParentIdsByChild($childProductId);

        if (empty($parentIds)) {
            return [];
        }

        $exportableParentIds = array_map(
            'intval',
            $this->exportableConfigurableParentsCollectionProvider->get($storeId)
                ->addIdFilter($parentIds)
                ->getAllIds()
        );
        sort($exportableParentIds);

        return $exportableParentIds;
    }
}
