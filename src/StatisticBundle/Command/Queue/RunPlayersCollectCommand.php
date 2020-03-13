<?php

namespace StatisticBundle\Command\Queue;

use Irev\MainBundle\Command\Queue\RedisQueueCommand;
use Irev\MainBundle\Document\Channel;
use StatisticBundle\Document\StatisticsRunPlayers;
use Irev\MainBundle\Queue\RedisQueue;
use StatisticBundle\Document\StatisticsApps;

class RunPlayersCollectCommand extends RedisQueueCommand
{
    private $channels = [];

    protected $flushInterval = 10;

    private $statistic;

    protected function getQueueKey()
    {
        return RedisQueue::KEY_RUN_PLAYER_STAT;
    }

    protected function doPrepare()
    {
        $this->channels = $this->loadChannels();
    }

    protected function handleRow($row)
    {
        $date = new \DateTime($row['date']);
        $date->setTimezone(new \DateTimeZone('Europe/Moscow'));

        if (!isset($this->channels[$row['channel_id']])) {
            $this->logger->error(sprintf('Канал %s не найден', $row['channel_id']));
            return;
        }

        $channelId = $this->channels[$row['channel_id']];

        $type = mb_strtolower($row['device_type']);

        if (!isset(StatisticsApps::$devicesTypes[$type])) {
            $this->logger->error(sprintf('Тип %s не найден', $type));
            return;
        }

        try {
            $deviceType = StatisticsApps::$devicesTypes[$type];
        } catch (\LogicException $e) {
            $this->logger->error(sprintf('Тип %s не найден', $type));
            return;
        }

        $this->statistic[] = [
            '_id' => StatisticsRunPlayers::generateId($date, $row['device_id'], $deviceType, $channelId),
            'date' => new \MongoDate($date->getTimestamp()),
            'deviceId' => $row['device_id'],
            'deviceType' => $deviceType,
            'channel' => (int)$channelId
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
        $collection = $dm->getDocumentCollection(StatisticsRunPlayers::class)->getMongoCollection();

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

    private function loadChannels()
    {
        $channels = $this
            ->container
            ->getMongoRepository(Channel::class)
            ->findAll()
        ;

        foreach ($channels as $channel) {
            /** @var Channel $channel */
            $this->channels[$channel->getId()] = $channel->getId();
        }

        return $this->channels;
    }
}
