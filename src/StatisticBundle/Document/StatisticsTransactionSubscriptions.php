<?php

namespace StatisticBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as Mongo;

/**
 * @Mongo\Document(collection="statistics_transaction_subscriptions")
 */
class StatisticsTransactionSubscriptions
{
    /**
     * @Mongo\Id(strategy="NONE", type="string")
     */
    private $id;
}
