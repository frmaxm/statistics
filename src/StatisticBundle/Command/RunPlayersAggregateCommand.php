<?php

namespace StatisticBundle\Command;

use Doctrine\ODM\MongoDB\MongoDBException;
use Irev\MainBundle\Command\BaseCommand;
use StatisticBundle\Document\StatisticsPlayers;
use StatisticBundle\Manager\StatisticsManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunPlayersAggregateCommand extends BaseCommand
{
    protected function configure()
    {
        $this->addArgument('from', InputArgument::OPTIONAL, 'From date', 'yesterday');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $from = new \DateTime($input->getArgument('from'));

        /** @var StatisticsManager $statManager */
        $statManager = $this->container->get(StatisticsManager::class);
        $dm = $this->container->getDocumentManager();
        try {
            $collection = $dm->getDocumentCollection(StatisticsPlayers::class)->getMongoCollection();
        } catch (MongoDBException $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $dates = [];
        while ($from <= new \DateTime()) {
            $dates[$from->format('Ymd')] = clone $from;
            $from->add(new \DateInterval('P1D'));
        }
        sort($dates);

        foreach ($dates as $date) {
            $this->logger->info(sprintf('Aggregation of video player launch data by date %s', $date->format('Y-m-d')));

            try {
                $items = $statManager->buildPlayers($date);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                continue;
            }

            $batch = new \MongoUpdateBatch($collection);
            foreach ($items as $item) {
                $batch->add([
                    'q' => ['_id' => $item['_id']],
                    'u' => [
                        '$set' => [
                            '_id' => $item['_id'],
                            'date' => new \MongoDate($item['date']),
                            'deviceType' => $item['deviceType'],
                            'channel' => $item['channel'],
                            'data' => [
                                'runAll' => (int)$item['runAll'],
                                'runUnique' => (int)$item['runUnique'],
                            ]
                        ]
                    ],
                    'upsert' => true
                ]);
            }

            try {
                $batch->execute();
            } catch (\MongoWriteConcernException $e) {
                $this->logger->error($e->getMessage());
                return;
            }
        }

        $statManager->removeRunPlayers();
    }
}
