<?php

namespace StatisticBundle\Manager;

use Irev\MainBundle\Document\Offer;
use Irev\MainBundle\ODM\Aggregator;
use StatisticBundle\Document\StatisticsTransactionCoins;
use StatisticBundle\Filter\StatisticsFilter;

class StatisticsTransactionCoinsManager extends StatisticsTransactionManager
{
    public function getStatistics(StatisticsFilter $filter)
    {
        $storeNames = [Offer::STORE_APPLE, Offer::STORE_GOOGLE];

        $aggregator = new Aggregator($this->dm, StatisticsTransactionCoins::class);

        $totalGroup = [
            '_id' => null,
            'totalSum' => ['$sum' => '$totalSum'],
            'totalCount' => ['$sum' => '$totalCount'],
        ];

        $stores = [];
        foreach ($storeNames as $store) {
            $stores[$store] = [
                'count' => '$' . $store . '_count',
                'sum' => '$' . $store . '_sum',
            ];

            $totalGroup[$store . '_count'] = ['$sum' => '$stores.' . $store . '.count'];
            $totalGroup[$store . '_sum'] = ['$sum' => '$stores.' . $store . '.sum'];
        }

        $group = $totalGroup;
        $group['_id'] = '$sku';

        $totalProject = [
            '_id' => 0,
            'totalSum' => 1,
            'totalCount' => 1,
            'stores' => $stores
        ];
        $project = array_merge($totalProject, [
            'sku' => '$_id',
        ]);

        $result = $aggregator->aggregate([
            ['$match' => $this->buildMatch($filter)],
            ['$group' => $group],
            ['$project' => $project]
        ]);

        $total = $aggregator->aggregate([
            ['$match' => $this->buildMatch($filter)],
            ['$group' => $totalGroup],
            ['$project' => $totalProject]
        ])[0];

        $skus = array_column($result, 'sku');

        $offers = $this->loadOffersBySku($skus);

        $default = array_fill_keys($storeNames, [
            'count' => 0,
            'sum' => 0
        ]);

        foreach ($result as &$row) {
            if (!isset($offers[$row['sku']])) {
                continue;
            }
            $row['offer'] = $offers[$row['sku']];

            foreach ($storeNames as $storeName) {
                if (!isset($row['stores'][$storeName])) {
                    $row['stores'][$storeName] = $default;
                }
            }
        }
        unset($row);

        return [
            'data' => $result,
            'total' => $total
        ];
    }
}
