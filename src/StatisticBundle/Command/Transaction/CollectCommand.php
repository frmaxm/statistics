<?php

namespace StatisticBundle\Command\Transaction;

use Doctrine\ODM\MongoDB\DocumentManager;
use Irev\MainBundle\Document\MobileDevice;
use Irev\MainBundle\Document\Offer;
use Irev\MainBundle\Document\SmartDevice;
use Irev\MainBundle\Document\Transaction;
use Irev\MainBundle\ODM\Aggregator;
use Psr\Log\LoggerInterface;
use StatisticBundle\Document\StatisticsTransactionCoins;
use StatisticBundle\Document\StatisticsTransactionPPV;
use StatisticBundle\Document\StatisticsTransactionSubscriptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CollectCommand extends Command
{
    /**
     * @var DocumentManager
     */
    protected $dm;

    protected static $defaultName = 'statistic:transaction:collect';

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(DocumentManager $dm, LoggerInterface $logger)
    {
        parent::__construct();

        $this->dm = $dm;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Collect transaction statistics')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, '', 'yesterday')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, '', 'today')
            ->addOption('no-coins', null, InputOption::VALUE_NONE)
            ->addOption('no-ppv', null, InputOption::VALUE_NONE)
            ->addOption('no-translations', null, InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $from = new \DateTime($input->getOption('from'));
        $to = new \DateTime($input->getOption('to'));

        $from->setTime(0, 0, 0);
        $to->setTime(23, 59, 59);

        $dates = [];
        while ($from <= $to) {
            $dates[$from->format('Ymd')] = clone $from;
            $from->add(new \DateInterval('P1D'));
        }
        sort($dates);

        if (!$input->getOption('no-coins')) {
            $this->collectCoinsStatistic($dates);
        }
        if (!$input->getOption('no-ppv')) {
            $this->collectPPVStatistic($dates);
        }
        if (!$input->getOption('no-translations')) {
            $this->collectTranslationStatistic($dates);
        }
    }

    private function collectCoinsStatistic(array $dates)
    {
        $this->logger->info('Collect coins statistics');

        $criteria = [
            'store' => ['$in' => [Offer::STORE_APPLE, Offer::STORE_GOOGLE]],
            'kind' => Offer::KIND_COINS
        ];

        $coinsOfferIds = $this->loadOffers($criteria);

        $aggregator = new Aggregator($this->dm, Transaction::class);

        /** @var \DateTime[] $dates */
        foreach ($dates as $date) {
            $from = clone $date;
            $to = clone $date;
            $from->setTime(0, 0, 0);
            $to->setTime(23, 59, 59);

            $this->logger->info(sprintf('%s', $date->format('Y-m-d')));

            $match = [
                'offer' => ['$in' => $coinsOfferIds],
                'createdAt' => [
                    '$gte' => new \MongoDate($from->getTimestamp()),
                    '$lte' => new \MongoDate($to->getTimestamp())
                ],
                'direction' => Transaction::DIRECTION_IN,
                'status' => Transaction::STATUS_APPROVED,
                'method' => Transaction::METHOD_PURCHASE,
                'productType' => Offer::KIND_COINS
            ];

            $result = $aggregator->aggregate([
                ['$match' => $match],
                ['$group' => [
                    '_id' => [
                        'sku' => '$sku',
                        'store' => '$store'
                    ],
                    'count' => ['$sum' => 1],
                    'sum' => ['$sum' => '$amount']
                ]],
                ['$group' => [
                    '_id' => '$_id.sku',
                    'totalSum' => ['$sum' => '$sum'],
                    'totalCount' => ['$sum' => '$count'],
                    'stores' => ['$push' => ['k' => '$_id.store', 'v' => ['count' => '$count', 'sum' => '$sum']]]
                ]],
                ['$project' => [
                    '_id' => ['$concat' => [$date->format('Ymd'), '_', '$_id']],
                    'sku' => '$_id',
                    'date' => $date->format('Y-m-d'),
                    'totalSum' => 1,
                    'totalCount' => 1,
                    'stores' => ['$arrayToObject' => '$stores']
                ]]
            ]);

            if (!$result) {
                continue;
            }

            $batch = new \MongoUpdateBatch($this->dm->getDocumentCollection(StatisticsTransactionCoins::class)->getMongoCollection());
            foreach ($result as $row) {
                $batch->add([
                    'q' => ['_id' => $row['_id']],
                    'u' => [
                        '$set' => $row
                    ],
                    'upsert' => true
                ]);
            }
            $batch->execute();
        }
    }

    private function loadOffers(array $criteria)
    {
        $ids = [];
        $result = $this->dm->getDocumentCollection(Offer::class)->find($criteria)->toArray();
        foreach ($result as $row) {
            $ids[] = $row['_id'];
        }

        return $ids;
    }

    private function collectPPVStatistic(array $dates)
    {
        $this->logger->info('Collect PPV statistics');

        $criteria = [
            'store' => ['$in' => [Offer::STORE_APPLE, Offer::STORE_GOOGLE]],
            'kind' => Offer::KIND_PPV
        ];

        $offerIds = $this->loadOffers($criteria);

        $aggregator = new Aggregator($this->dm, Transaction::class);

        $collection = $this->dm->getDocumentCollection(StatisticsTransactionPPV::class);

        /** @var \DateTime[] $dates */
        foreach ($dates as $date) {
            $from = clone $date;
            $to = clone $date;
            $from->setTime(0, 0, 0);
            $to->setTime(23, 59, 59);

            $this->logger->info(sprintf('%s', $date->format('Y-m-d')));

            $match = [
                'offer' => ['$in' => $offerIds],
                'createdAt' => [
                    '$gte' => new \MongoDate($from->getTimestamp()),
                    '$lte' => new \MongoDate($to->getTimestamp())
                ],
                'direction' => Transaction::DIRECTION_IN,
                'status' => Transaction::STATUS_APPROVED,
                'method' => Transaction::METHOD_PURCHASE,
                'productType' => Offer::KIND_PPV,
                'ppvInfo.id' => ['$exists' => true]
            ];

            $result = $aggregator->aggregate([
                ['$match' => $match],
                ['$group' => [
                    '_id' => [
                        'sku' => '$sku',
                        'store' => '$store',
                        'deviceType' => ['$cond' => [
                            'if' => ['$eq' => ['$device.type', SmartDevice::TYPE_SMART]],
                            'then' => SmartDevice::TYPE_SMART,
                            'else' => MobileDevice::TYPE_MOBILE
                        ]],
                        'event' => ['$toInt' => '$ppvInfo.id'],
                        'title' => '$ppvInfo.title',
                        'mediaId' => '$ppvInfo.mediaId'
                    ],
                    'count' => ['$sum' => 1],
                    'sum' => ['$sum' => '$amount']
                ]],
                ['$project' => [
                    '_id' => false,
                    'date' => $date->format('Y-m-d'),
                    'event' => [
                        '_id' => '$_id.event',
                        'title' => '$_id.title',
                        'mediaId' => '$_id.mediaId'
                    ],
                    'sku' => '$_id.sku',
                    'store' => '$_id.store',
                    'deviceType' => '$_id.deviceType',
                    'count' => 1,
                    'sum' => 1
                ]]
            ]);

            // remove all data from $date
            $collection->remove(['date' => $date->format('Y-m-d')]);

            if (!$result) {
                continue;
            }

            $batch = new \MongoInsertBatch($collection->getMongoCollection());
            foreach ($result as $row) {
                $batch->add($row);
            }
            $batch->execute();
        }
    }

    private function collectTranslationStatistic(array $dates)
    {
        $this->logger->info('Collect subscriptions statistics');

        $criteria = [
            'store' => ['$in' => [Offer::STORE_APPLE, Offer::STORE_GOOGLE]],
            'kind' => Offer::KIND_TRANSLATION
        ];

        $offerIds = $this->loadOffers($criteria);

        $aggregator = new Aggregator($this->dm, Transaction::class);

        $collection = $this->dm->getDocumentCollection(StatisticsTransactionSubscriptions::class);

        /** @var \DateTime[] $dates */
        foreach ($dates as $date) {
            $from = clone $date;
            $to = clone $date;
            $from->setTime(0, 0, 0);
            $to->setTime(23, 59, 59);

            $this->logger->info(sprintf('%s', $date->format('Y-m-d')));

            $match = [
                'offer' => ['$in' => $offerIds],
                'createdAt' => [
                    '$gte' => new \MongoDate($from->getTimestamp()),
                    '$lte' => new \MongoDate($to->getTimestamp())
                ],
                'direction' => Transaction::DIRECTION_IN,
                'status' => Transaction::STATUS_APPROVED,
                'method' => Transaction::METHOD_PURCHASE,
                'productType' => Offer::KIND_TRANSLATION
            ];

            $result = $aggregator->aggregate([
                ['$match' => $match],
                ['$group' => [
                    '_id' => [
                        'sku' => '$sku',
                        'store' => '$store',
                        'deviceType' => ['$cond' => [
                            'if' => ['$eq' => ['$device.type', SmartDevice::TYPE_SMART]],
                            'then' => SmartDevice::TYPE_SMART,
                            'else' => MobileDevice::TYPE_MOBILE
                        ]],
                        'purchaseType' => ['$cond' => [
                            'if' => ['$gt' => ['$parent', null]],
                            'then' => 'renewal',
                            'else' => 'new'
                        ]]
                    ],
                    'count' => ['$sum' => 1],
                    'sum' => ['$sum' => '$amount']
                ]],
                ['$project' => [
                    '_id' => false,
                    'date' => $date->format('Y-m-d'),
                    'sku' => '$_id.sku',
                    'store' => '$_id.store',
                    'deviceType' => '$_id.deviceType',
                    'purchaseType' => '$_id.purchaseType',
                    'count' => 1,
                    'sum' => 1
                ]]
            ]);

            // remove all data from $date
            $collection->remove(['date' => $date->format('Y-m-d')]);

            if (!$result) {
                continue;
            }

            $batch = new \MongoInsertBatch($collection->getMongoCollection());
            foreach ($result as $row) {
                $batch->add($row);
            }
            $batch->execute();
        }
    }
}
