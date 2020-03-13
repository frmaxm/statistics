<?php

namespace StatisticBundle\Manager;

use Irev\MainBundle\ODM\Aggregator;
use StatisticBundle\Document\StatisticsTransactionPPV;
use StatisticBundle\Filter\StatisticsFilter;

class StatisticsTransactionPPVManager extends StatisticsTransactionManager
{
    public function getStatistics(StatisticsFilter $filter)
    {
        $aggregator = new Aggregator($this->dm, StatisticsTransactionPPV::class);

        $match = $this->buildMatch($filter);

        $total = $aggregator->aggregate([
            ['$match' => $match],
            ['$group' => [
                '_id' => [
                    'sku' => '$sku',
                    'store' => '$store',
                    'deviceType' => '$deviceType'
                ],
                'sum' => ['$sum' => '$sum'],
                'count' => ['$sum' => '$count'],
            ]],
            ['$group' => [
                '_id' => [
                    'sku' => '$_id.sku',
                    'store' => '$_id.store',
                ],
                'sum' => ['$sum' => '$sum'],
                'count' => ['$sum' => '$count'],
                'deviceTypes' => ['$push' => ['k' => '$_id.deviceType', 'v' => ['count' => '$count', 'sum' => '$sum']]]
            ]],
            ['$project' => [
                '_id' => 1,
                'sum' => 1,
                'count' => 1,
                'deviceTypes' => ['$arrayToObject' => '$deviceTypes']
            ]],
            ['$group' => [
                '_id' => [
                    'sku' => '$_id.sku',
                ],
                'sum' => ['$sum' => '$sum'],
                'count' => ['$sum' => '$count'],
                'stores' => ['$push' => ['k' => '$_id.store', 'v' => ['count' => '$count', 'sum' => '$sum', 'deviceTypes' => '$deviceTypes']]]
            ]],
            ['$project' => [
                '_id' => 1,
                'sum' => 1,
                'count' => 1,
                'stores' => ['$arrayToObject' => '$stores']
            ]],
            ['$group' => [
                '_id' => null,
                'offers' => ['$push' => ['k' => '$_id.sku', 'v' => ['stores' => '$stores', 'sum' => '$sum', 'count' => '$count']]],
                'sum' => ['$sum' => '$sum'],
                'count' => ['$sum' => '$count'],
            ]],
            ['$project' => [
                '_id' => false,
                'count' => true,
                'sum' => true,
                'offers' => ['$arrayToObject' => '$offers'],
            ]]
        ]);

        $data = $aggregator->aggregate([
            ['$match' => $match],
            ['$group' => [
                '_id' => [
                    'sku' => '$sku',
                    'store' => '$store',
                    'event' => '$event._id',
                    'title' => '$event.title',
                    'mediaId' => '$event.mediaId'
                ],
                'sum' => ['$sum' => '$sum'],
                'count' => ['$sum' => '$count'],
                'deviceTypes' => ['$push' => ['k' => '$deviceType', 'v' => ['count' => '$count', 'sum' => '$sum']]]
            ]],
            ['$project' => [
                '_id' => 1,
                'sum' => 1,
                'count' => 1,
                'deviceTypes' => ['$arrayToObject' => '$deviceTypes']
            ]],
            ['$group' => [
                '_id' => [
                    'sku' => '$_id.sku',
                    'event' => '$_id.event',
                    'title' => '$_id.title',
                    'mediaId' => '$_id.mediaId'
                ],
                'sum' => ['$sum' => '$sum'],
                'count' => ['$sum' => '$count'],
                'stores' => ['$push' => ['k' => '$_id.store', 'v' => ['count' => '$count', 'sum' => '$sum', 'deviceTypes' => '$deviceTypes']]]
            ]],
            ['$project' => [
                '_id' => 1,
                'sum' => 1,
                'count' => 1,
                'stores' => ['$arrayToObject' => '$stores']
            ]],
            ['$group' => [
                '_id' => [
                    'event' => '$_id.event',
                    'title' => '$_id.title',
                    'mediaId' => '$_id.mediaId'
                ],
                'offers' => ['$push' => ['k' => '$_id.sku', 'v' => ['stores' => '$stores', 'sum' => '$sum', 'count' => '$count']]],
                'sum' => ['$sum' => '$sum'],
                'count' => ['$sum' => '$count'],
            ]],
            ['$project' => [
                '_id' => true,
                'count' => true,
                'sum' => true,
                'offers' => ['$arrayToObject' => '$offers'],
            ]]
        ]);

        return [
            'data' => $data,
            'total' => $total ? $total[0] : []
        ];
    }
}
