<?php

namespace StatisticBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as Mongo;
use Irev\MainBundle\Document\Channel;

abstract class StatisticsPlayersData
{
    /**
     * @Mongo\Field(type="date")
     */
    protected $date;

    /**
     * @Mongo\Field(type="string")
     */
    protected $deviceType;

    /**
     * @var Channel
     * @Mongo\ReferenceOne(targetDocument="Irev\MainBundle\Document\Channel", storeAs="id")
     */
    protected $channel;

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    public function getDeviceType()
    {
        return $this->deviceType;
    }

    public function setDeviceType($deviceType)
    {
        $this->deviceType = $deviceType;
        return $this;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function setChannel(Channel $channel)
    {
        $this->channel = $channel;
        return $this;
    }
}
