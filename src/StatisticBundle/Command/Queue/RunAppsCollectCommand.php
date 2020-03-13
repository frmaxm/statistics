<?php

namespace StatisticBundle\Command\Queue;

use Irev\MainBundle\Command\Queue\RedisQueueCommand;
use Irev\MainBundle\Queue\RedisQueue;
use StatisticBundle\Document\StatisticsApps;
use StatisticBundle\Document\StatisticsRunApps;

class RunAppsCollectCommand extends RedisQueueCommand
{
    protected $flushInterval = 10;

    private $statistic;

    protected function getQueueKey()
    {
        return RedisQueue::KEY_RUN_APP_STAT;
    }

    protected function handleRow($row)
    {
        $date = new \DateTime($row['date']);
        $date->setTimezone(new \DateTimeZone('Europe/Moscow'));
        $type = strtolower($row['device_type']);

        if (!isset(StatisticsApps::$devicesTypes[$type])) {
            throw new \RuntimeException(sprintf('Тип %s не найден', $type));
        }

        $deviceType = StatisticsApps::$devicesTypes[$type];
        $this->statistic[] = [
            '_id' => StatisticsRunApps::generateId($date, $row['device_id'], $row['device_type']),
            'date' => new \MongoDate($date->getTimestamp()),
            'deviceId' => $row['device_id'],
            'deviceType' => $deviceType
        ];
    }

    protected function doFlush()
    {
        if (!$this->statistic) {
            return;
        }
        $data = array_chunk($this->statistic, $this->batchSize, true);
        $this->statistic = [];

        $dm = $this->container->getDocumentManager();
        $collection = $dm->getDocumentCollection(StatisticsRunApps::class)->getMongoCollection();


        foreach ($data as $chunk) {
            $batch = new \MongoInsertBatch($collection);

            foreach ($chunk as $row) {
                $batch->add($row);
            }

            try {
                $batch->execute();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
}
