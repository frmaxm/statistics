<?php

namespace StatisticBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as Mongo;

/**
 * @Mongo\Document(collection="statistics_transaction_ppv")
 */
class StatisticsTransactionPPV
{
    /**
     * @Mongo\Id()
     */
    private $id;
}
