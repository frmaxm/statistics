<?php

namespace StatisticBundle\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Irev\MainBundle\Document\MobileDevice;
use Irev\MainBundle\Document\SmartDevice;
use Irev\MainBundle\ODM\Aggregator;
use StatisticBundle\Document\StatisticsApps;
use StatisticBundle\Document\StatisticsRunApps;
use StatisticBundle\Document\StatisticsRunPlayers;
use StatisticBundle\Document\StatisticsPlayers;
use StatisticBundle\Document\StatisticsUsers;
use StatisticBundle\Filter\StatisticsFilter;

class StatisticsManager
{
    /**
     * @var DocumentManager
     */
    private $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function get(StatisticsFilter $filter)
    {
        $totalData = (new Aggregator($this->dm, StatisticsUsers::class))->aggregate([
            ['$match' => [
                'date' => [
                    '$gte' => new \MongoDate($filter->getDateRange()->getFrom()->getTimestamp()),
                    '$lte' => new \MongoDate($filter->getDateRange()->getTo()->getTimestamp())
                ]
            ]],
            ['$group' => [
                '_id' => null,
                'users_new' => ['$sum' => '$users.new'],
                'complete_registration' => ['$sum' => '$users.completeRegistration'],
                'imported' => ['$sum' => '$users.importedOldAccount'],
            ]]
        ]);

        $total = new StatisticsUsers();
        if ($totalData) {
            $data = $totalData[0];
            $total
                ->getUsers()
                    ->setNew((int)$data['users_new'])
                    ->setCompleteRegistration((int)$data['complete_registration'])
                    ->setImportedOldAccount((int)$data['imported'])
            ;
        }

        $repo = $this->dm->getRepository(StatisticsUsers::class);
        $qb = $repo->createFilteredQB($filter);
        $items = $qb->getQuery()->toArray();

        return [
            'items' => $items,
            'total' => $total
        ];
    }

    public function buildApps(\DateTime $date)
    {
        $dateFrom = clone $date;
        $dateFrom->setTime(0, 0, 0);
        $dateEnd = clone $date;
        $dateEnd->setTime(23, 59, 59);

        $totalData = (new Aggregator($this->dm, StatisticsRunApps::class))->aggregate([
            ['$match' => [
                'date' => [
                    '$gte' => new \MongoDate($dateFrom->getTimestamp()),
                    '$lte' => new \MongoDate($dateEnd->getTimestamp()),
                ]
            ]],
            ['$group' => [
                '_id' => '$deviceType',
                'deviceIds' => [
                    '$addToSet' => '$deviceId'
                ],
                'total' => [
                    '$sum' => 1
                ]
            ]],
            ['$unwind' => '$deviceIds'],
            ['$group' => [
                '_id' => [
                    'device_type' => '$_id',
                    'run_all' => '$total',
                ],
                'run_unique' => ['$sum' => 1]
            ]]
        ]);

        $mobileData = (new Aggregator($this->dm, MobileDevice::class))->aggregate([
            ['$match' => [
                'createdAt' => [
                    '$gte' => new \MongoDate($dateFrom->getTimestamp()),
                    '$lte' => new \MongoDate($dateEnd->getTimestamp()),
                ],
                'os' => ['$nin' => [null, '']]
            ]],
            ['$group' => [
                '_id' => '$os',
                'run_new' => ['$sum' => 1]
            ]],
            ['$project' => [
                'device_type' => '$_id',
                'run_new' => '$run_new'
            ]]
        ]);

        $newSmartDevices = $this
            ->dm
            ->getRepository(SmartDevice::class)
            ->createQueryBuilder()
            ->field('createdAt')->gte($dateFrom)->lte($dateEnd)
            ->getQuery()
            ->count()
        ;

        $totalDataArr = array_combine(array_map(function($item) use ($date) {
            return sprintf('%s:%s', $date->getTimestamp(), $item['_id']['device_type']);
        }, $totalData), $totalData);

        $totalData = [];
        foreach ($totalDataArr as $k => $v) {
            $totalData[$k] = $v['_id'];
            $totalData[$k]['run_unique'] = $v['run_unique'];
        }

        $mobileData = array_combine(array_map(function($item) use ($date) {
            return sprintf('%s:%s', $date->getTimestamp(), $item['_id']);
        }, $mobileData), $mobileData);

        $smartArray[sprintf('%s:%s', $date->getTimestamp(), 'smart')] = [
            'device_type' => 'smart',
            'run_new' => $newSmartDevices
        ];

        $recursiveArr = array_replace_recursive($totalData, $smartArray, $mobileData);

        $resultTotalArray = array_map(function($item) {
            $item = array_merge([
                'run_new' => 0,
                'run_all' => 0,
                'run_unique' => 0,
            ], $item);

            return $item;
        }, $recursiveArr);

        $items = [];
        foreach ($resultTotalArray as $item) {
            $items[] = [
                '_id' => StatisticsApps::generateId($date, $item['device_type']),
                'date' => $date->getTimestamp(),
                'deviceType' => $item['device_type'],
                'runAll' => $item['run_all'],
                'runUnique' => $item['run_unique'],
                'new' => $item['run_new']
            ];
        }

        return $items;
    }

    public function removeRunApps()
    {
        return $this
            ->dm
            ->getRepository(StatisticsRunApps::class)
            ->createQueryBuilder()
            ->field('date')->lte(new \DateTime('-7 day'))
            ->remove()
        ;
    }

    public function removeRunPlayers()
    {
        return $this
            ->dm
            ->getRepository(StatisticsRunPlayers::class)
            ->createQueryBuilder()
            ->field('date')->lte(new \DateTime('-7 day'))
            ->remove()
        ;
    }

    public function buildPlayers(\DateTime $date)
    {
        $dateFrom = clone $date;
        $dateFrom->setTime(0, 0, 0);
        $dateEnd = clone $date;
        $dateEnd->setTime(23, 59, 59);

        $itemsArr = (new Aggregator($this->dm, StatisticsRunPlayers::class))->aggregate([
            ['$match' => [
                'date' => [
                    '$gte' => new \MongoDate($dateFrom->getTimestamp()),
                    '$lte' => new \MongoDate($dateEnd->getTimestamp()),
                ]
            ]],
            ['$group' => [
                '_id' => [
                    'deviceType' => '$deviceType',
                    'channel' => '$channel',
                ],
                'deviceIds' => [
                    '$addToSet' => '$deviceId'
                ],
                'total' => [
                    '$sum' => 1
                ]
            ]],
            ['$unwind' => '$deviceIds'],
            ['$group' => [
                '_id' => [
                    'device_type' => '$_id.deviceType',
                    'channel' => '$_id.channel',
                    'run_all' => '$total'
                ],
                'run_unique' => ['$sum' => 1]
            ]],
            ['$sort' => [
                'channel' => 1
            ]]
        ]);

        $items = [];
        foreach ($itemsArr as $k => $v) {
            $items[$k] = $v['_id'];
            $items[$k]['run_unique'] = $v['run_unique'];
        }

        $data = [];
        foreach ($items as $item) {
            $data[] = [
                '_id' => StatisticsPlayers::generateId($date, $item['device_type'], $item['channel']),
                'date' => $date->getTimestamp(),
                'deviceType' => $item['device_type'],
                'channel' => $item['channel'],
                'runAll' => $item['run_all'],
                'runUnique' => $item['run_unique']
            ];
        }

        return $data;
    }

    public function getTotalRunApps(StatisticsFilter $filter)
    {
        $match = $this->applyFilter($filter);

        $items = (new Aggregator($this->dm, StatisticsApps::class))->aggregate([
            ['$match' => $match],
            ['$group' => [
                '_id' => null,
                '_new' => ['$sum' => '$new'],
                '_all' => ['$sum' => '$data.runAll'],
                '_unique' => ['$sum' => '$data.runUnique']
            ]]
        ]);

        if (!$items) {
            return [
                '_new' => 0,
                '_all' => 0,
                '_unique' => 0
            ];
        }


        return $items[0];
    }

    public function getTotalRunPlayers(StatisticsFilter $filter)
    {
        $match = $this->applyFilter($filter);

        $items = (new Aggregator($this->dm, StatisticsPlayers::class))->aggregate([
            ['$match' => $match],
            ['$group' => [
                '_id' => null,
                '_all' => ['$sum' => '$data.runAll'],
                '_unique' => ['$sum' => '$data.runUnique']
            ]]
        ]);

        if (!$items) {
            return [
                '_new' => 0,
                '_all' => 0,
                '_unique' => 0
            ];
        }

        return $items[0];
    }

    private function applyFilter(StatisticsFilter $filter)
    {
        $match = [
            'date' => [
                '$gte' => new \MongoDate($filter->getDateRange()->getFrom()->getTimestamp()),
                '$lte' => new \MongoDate($filter->getDateRange()->getTo()->getTimestamp())
            ],
        ];

        if ($filter->getChannel()) {
            $match = array_merge($match, [
                'channel' => [
                    '$eq' => $filter->getChannel()->getId()
                ]
            ]);
        }

        return $match;
    }
}
