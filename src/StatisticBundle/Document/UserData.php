<?php

namespace StatisticBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as Mongo;

/**
 * @Mongo\EmbeddedDocument()
 */
class UserData
{
    /**
     * @Mongo\Field(type="int")
     */
    private $new = 0;

    /**
     * @Mongo\Field(type="int")
     */
    private $completeRegistration = 0;

    /**
     * @Mongo\Field(type="int")
     */
    private $importedOldAccount = 0;

    public function getNew()
    {
        return $this->new;
    }

    public function setNew($new)
    {
        $this->new = $new;
        return $this;
    }

    public function getCompleteRegistration()
    {
        return $this->completeRegistration;
    }

    public function setCompleteRegistration($completeRegistration)
    {
        $this->completeRegistration = $completeRegistration;
        return $this;
    }

    public function getImportedOldAccount()
    {
        return $this->importedOldAccount;
    }

    public function setImportedOldAccount($importedOldAccount)
    {
        $this->importedOldAccount = $importedOldAccount;
        return $this;
    }
}
