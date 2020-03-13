<?php

namespace StatisticBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as Mongo;

/**
 * @Mongo\Document(collection="statistics_run_players", repositoryClass="StatisticBundle\Document\Repository\StatisticsRunPlayersRepository")
 * @Mongo\Index(keys={"deviceId": "asc"}, {"name": "__idx_device_id"})
 * @Mongo\HasLifecycleCallbacks()
 */
class StatisticsRunPlayers extends StatisticsPlayersData
{
    /**
     * @Mongo\Id(strategy="NONE", type="string")
     */
    private $id;

    /**
     * @Mongo\Field(type="string")
     */
    private $deviceId;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getDeviceId()
    {
        return $this->deviceId;
    }

    public function setDeviceId($deviceId)
    {
        $this->deviceId = $deviceId;
        return $this;
    }

    public static function generateId(\DateTime $date, $deviceId, $deviceType, $channelId)
    {
        return md5(sprintf('%s:%s:%s:%s:%s', $date->getTimestamp(), $deviceId, $deviceType, $channelId, microtime()));
    }
}
