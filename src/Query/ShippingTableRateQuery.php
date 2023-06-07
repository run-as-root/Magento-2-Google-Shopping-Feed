<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class ShippingTableRateQuery
{
    private ResourceConnection $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function get(array $countries, string $conditionName): Select
    {
        $select = $this->resourceConnection
            ->getConnection()
            ->select()
            ->from($this->resourceConnection->getTableName('shipping_tablerate'))
            ->where('condition_name = ?', $conditionName);

        if (!empty($countries)) {
            $select->where('dest_country_id in (?)', $countries);
        }

        return $select;
    }
}
