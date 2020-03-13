<?php

namespace StatisticBundle\Manager;

use Irev\MainBundle\Document\Offer;
use Irev\MainBundle\ODM\Aggregator;
use StatisticBundle\Document\StatisticsTransactionSubscriptions;
use StatisticBundle\Filter\StatisticsFilter;

class StatisticsTransactionSubscriptionsManager extends StatisticsTransactionManager
{
    public function getStatistics(StatisticsFilter $filter)
    {
        $match = $this->buildMatch($filter);

        if ($filter->getProduct()) {
            $offers = $this->dm->getRepository(Offer::class)->findBy(['product' => $filter->getProduct()]);

            $skus = array_map(static function (Offer $offer) {
                return $offer->getSku();
            }, $offers);
            $skus = array_unique($skus);

            if ($skus) {
                $match['sku'] = ['$in' => array_values($skus)];
            }
        }

        $aggregator = new Aggregator($this->dm, StatisticsTransactionSubscriptions::class);
        
        $result = $aggregator->aggregate([
            ['$facet' => [
                'byStores' => [
                    ['$match' => $match],
                    ['$group' => [
                        '_id' => [
                            'sku' => '$sku',
                            'store' => '$store',
                            'deviceType' => '$deviceType'
                        ],
                        'count' => ['$sum' => '$count'],
                        'sum' => ['$sum' => '$sum'],
                    ]],
                    ['$group' => [
                        '_id' => [
                            'sku' => '$_id.sku',
                            'store' => '$_id.store',
                        ],
                        'count' => ['$sum' => '$count'],
                        'sum' => ['$sum' => '$sum'],
                        'devices' => ['$push' => ['k' => '$_id.deviceType', 'v' => ['count' => '$count', 'sum' => '$sum']]]
                    ]],
                    ['$project' => [
                        '_id' => true,
                        'count' => true,
                        'sum' => true,
                        'devices' => ['$arrayToObject' => '$devices']
                    ]],
                    ['$group' => [
                        '_id' => '$_id.sku',
                        'count' => ['$sum' => '$count'],
                        'sum' => ['$sum' => '$sum'],
                        'stores' => ['$push' => ['k' => '$_id.store', 'v' => ['count' => '$count', 'sum' => '$sum', 'devices' => '$devices']]]
                    ]],
                    ['$project' => [
                        '_id' => false,
                        'sku' => '$_id',
                        'count' => true,
                        'sum' => true,
                        'stores' => ['$arrayToObject' => '$stores']
                    ]],
                    ['$sort' => ['sum' => -1]]
                ],
                'byPurchaseType' => [
                    ['$match' => $match],
                    ['$group' => [
                        '_id' => [
                            'sku' => '$sku',
                            'purchaseType' => '$purchaseType',
                        ],
                        'count' => ['$sum' => '$count'],
                        'sum' => ['$sum' => '$sum']
                    ]],
                    ['$group' => [
                        '_id' => '$_id.sku',
                        'purchaseTypes' => ['$push' => ['k' => '$_id.purchaseType', 'v' => ['count' => '$count', 'sum' => '$sum']]]
                    ]],
                    ['$project' => [
                        '_id' => false,
                        'sku' => '$_id',
                        'purchaseTypes' => ['$arrayToObject' => '$purchaseTypes']
                    ]]
                ]
            ]]
        ])[0];

        $result['byPurchaseType'] = array_combine(array_column($result['byPurchaseType'], 'sku'), $result['byPurchaseType']);

        $skuList = array_column($result['byStores'], 'sku');

        $offers = $this->loadOffersBySku($skuList);

        foreach ($result['byStores'] as &$row) {
            if (!isset($offers[$row['sku']])) {
                continue;
            }
            $row['offer'] = $offers[$row['sku']];
        }
        unset($row);

        $total = $aggregator->aggregate([
            ['$facet' => [
                'byStores' => [
                    ['$match' => $match],
                    ['$group' => [
                        '_id' => [
                            'store' => '$store',
                            'deviceType' => '$deviceType'
                        ],
                        'count' => ['$sum' => '$count'],
                        'sum' => ['$sum' => '$sum'],
                    ]],
                    ['$group' => [
                        '_id' => [
                            'store' => '$_id.store',
                        ],
                        'count' => ['$sum' => '$count'],
                        'sum' => ['$sum' => '$sum'],
                        'devices' => ['$push' => ['k' => '$_id.deviceType', 'v' => ['count' => '$count', 'sum' => '$sum']]]
                    ]],
                    ['$project' => [
                        '_id' => true,
                        'count' => true,
                        'sum' => true,
                        'devices' => ['$arrayToObject' => '$devices']
                    ]],
                    ['$group' => [
                        '_id' => null,
                        'count' => ['$sum' => '$count'],
                        'sum' => ['$sum' => '$sum'],
                        'stores' => ['$push' => ['k' => '$_id.store', 'v' => ['count' => '$count', 'sum' => '$sum', 'devices' => '$devices']]]
                    ]],
                    ['$project' => [
                        '_id' => false,
                        'count' => true,
                        'sum' => true,
                        'stores' => ['$arrayToObject' => '$stores']
                    ]],
                ],
                'byPurchaseType' => [
                    ['$match' => $match],
                    ['$group' => [
                        '_id' => [
                            'purchaseType' => '$purchaseType',
                        ],
                        'count' => ['$sum' => '$count'],
                        'sum' => ['$sum' => '$sum']
                    ]],
                    ['$group' => [
                        '_id' => null,
                        'purchaseTypes' => ['$push' => ['k' => '$_id.purchaseType', 'v' => ['count' => '$count', 'sum' => '$sum']]]
                    ]],
                    ['$project' => [
                        '_id' => false,
                        'purchaseTypes' => ['$arrayToObject' => '$purchaseTypes']
                    ]]
                ]
            ]]
        ])[0];

        return [
            'data' => $result,
            'total' => $total
        ];
    }
}
