<?php

namespace StatisticBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as Mongo;

/**
 * @Mongo\Document(collection="statistics_users", repositoryClass="StatisticBundle\Document\Repository\StatisticsUsersRepository")
 * @Mongo\UniqueIndex(keys={"date": "asc"}, {"name":"__idx_date"})
 *
 * @Mongo\HasLifecycleCallbacks()
 */
class StatisticsUsers
{
    /**
     * @Mongo\Id()
     */
    private $id;

    /**
     * @Mongo\Field(type="date")
     */
    private $date;

    /**
     * @Mongo\EmbedOne(targetDocument="UserData")
     */
    private $users;

    public function __construct()
    {
        $this->users = new UserData();
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

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    public function getUsers()
    {
        return $this->users;
    }

    public function setUsers($users)
    {
        $this->users = $users;
        return $this;
    }
}
