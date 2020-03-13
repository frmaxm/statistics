<?php
namespace StatisticBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as Mongo;

/**
 * @Mongo\EmbeddedDocument()
 */
class AppPlayerData
{
    /**
     * @Mongo\Field(type="int")
     */
    private $runAll = 0;

    /**
     * @Mongo\Field(type="int")
     */
    private $runUnique = 0;

    public function getRunAll()
    {
        return $this->runAll;
    }

    public function setRunAll($runAll)
    {
        $this->runAll = $runAll;
        return $this;
    }

    public function getRunUnique()
    {
        return $this->runUnique;
    }

    public function setRunUnique($runUnique)
    {
        $this->runUnique = $runUnique;
        return $this;
    }
}
