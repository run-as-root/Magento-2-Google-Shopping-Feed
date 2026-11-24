<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Query;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class ShippingTableRateQuery
{
    public const ANY_COUNTRY_ID = '0';

    private ResourceConnection $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param string[] $countries
     */
    public function get(array $countries, string $conditionName, ?int $websiteId = null): Select
    {
        $select = $this->resourceConnection
            ->getConnection()
            ->select()
            ->from([ 'tablerate' => $this->resourceConnection->getTableName('shipping_tablerate') ])
            ->joinLeft(
                [ 'region' => $this->resourceConnection->getTableName('directory_country_region') ],
                'region.region_id = tablerate.dest_region_id',
                [ 'region_code' => 'region.code', 'region_country_id' => 'region.country_id' ]
            )
            ->where('tablerate.condition_name = ?', $conditionName);

        if ($websiteId !== null) {
            $select->where('tablerate.website_id = ?', $websiteId);
        }

        if (!empty($countries)) {
            $select->where('tablerate.dest_country_id in (?)', [ ...$countries, self::ANY_COUNTRY_ID ]);
        }

        return $select;
    }
}
