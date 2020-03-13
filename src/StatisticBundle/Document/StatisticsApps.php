<?php

namespace StatisticBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as Mongo;
use Irev\MainBundle\Document\MobileDevice;

/**
 * @Mongo\Document(collection="statistics_apps", repositoryClass="StatisticBundle\Document\Repository\StatisticsAppsRepository")
 * @Mongo\HasLifecycleCallbacks()
 */
class StatisticsApps
{
    const TYPE_SMART = 'smart';

    const DEVICE_ANDROID = 'android';
    const DEVICE_ANDROID_TV = 'androidtv';
    const DEVICE_IOS = 'ios';
    const DEVICE_TV_OS = 'tvos';
    const DEVICE_SMART_SAMSUNG = 'samsung';
    const DEVICE_SMART_LG = 'lg';
    const DEVICE_SMART_OTHER = 'browser';

    public static $devicesTypes = [
        self::DEVICE_ANDROID => MobileDevice::OS_ANDROID,
        self::DEVICE_ANDROID_TV => MobileDevice::OS_TV_ANDROID,
        self::DEVICE_IOS => MobileDevice::OS_IOS,
        self::DEVICE_TV_OS => MobileDevice::OS_TV_OS,
        self::DEVICE_SMART_SAMSUNG => self::TYPE_SMART,
        self::DEVICE_SMART_LG => self::TYPE_SMART,
        self::DEVICE_SMART_OTHER => self::TYPE_SMART,
        self::TYPE_SMART => self::TYPE_SMART
    ];

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
    private $deviceType;

    /**
     * @Mongo\Field(type="int")
     */
    private $new = 0;

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
        $this->updateId();
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

    public function getNew()
    {
        return $this->new;
    }

    public function setNew($new)
    {
        $this->new = $new;
        return $this;
    }

    public static function generateId(\DateTime $date, $deviceType)
    {
        return md5(sprintf('%s:%s', $date->getTimestamp(), $deviceType));
    }

    protected function updateId()
    {
        if (!$this->date || !$this->deviceType) {
            return;
        }

        $this->id = self::generateId($this->date, $this->deviceType);
    }
}
