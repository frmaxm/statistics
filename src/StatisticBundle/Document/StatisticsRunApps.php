<?php

namespace StatisticBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as Mongo;

/**
 * @Mongo\Document(collection="statistics_run_apps", repositoryClass="StatisticBundle\Document\Repository\StatisticsRunAppsRepository")
 * @Mongo\HasLifecycleCallbacks()
 */
class StatisticsRunApps
{
    /**
     * @Mongo\Id(strategy="NONE", type="string")
     */
    private $id;

    /**
     * @Mongo\Field(type="date")
     */
    private $date;

    /**
     * @Mongo\Field(type="string")
     */
    private $deviceId;

    /**
     * @Mongo\Field(type="string")
     */
    private $deviceType;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date)
    {
        $this->date = $date;
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

    public function getDeviceType()
    {
        return $this->deviceType;
    }

    public function setDeviceType($deviceType)
    {
        $this->deviceType = $deviceType;
        return $this;
    }

    public static function generateId(\DateTime $date, $deviceId, $deviceType)
    {
        return md5(sprintf('%s:%s:%s:%s', $date->getTimestamp(), $deviceId, $deviceType, microtime()));
    }
}
