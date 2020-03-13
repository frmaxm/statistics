<?php

namespace StatisticBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as Mongo;

/**
 * @Mongo\Document(collection="statistics_players", repositoryClass="StatisticBundle\Document\Repository\StatisticsPlayersRepository")
 * @Mongo\HasLifecycleCallbacks()
 */
class StatisticsPlayers extends StatisticsPlayersData
{
    /**
     * @Mongo\Id(strategy="NONE", type="string")
     */
    private $id;

    /**
     * @Mongo\EmbedOne(targetDocument="AppPlayerData")
     */
    private $data;

    public function __construct()
    {
        $this->data = new AppPlayerData();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public static function generateId(\DateTime $date, $deviceType, $channel)
    {
        return md5(sprintf('%s:%s:%s', $date->getTimestamp(), $deviceType, $channel));
    }
}
