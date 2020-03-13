<?php

namespace StatisticBundle\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Irev\MainBundle\Document\Offer;
use StatisticBundle\Filter\StatisticsFilter;

abstract class StatisticsTransactionManager
{
    /**
     * @var DocumentManager
     */
    protected $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    abstract public function getStatistics(StatisticsFilter $filter);

    protected function loadOffersBySku(array $skus)
    {
        $offers = $this->dm->getRepository(Offer::class)->findBy(['sku' => ['$in' => $skus]]);
        $assoc = [];
        foreach ($offers as $offer) {
            $assoc[$offer->getSku()] = [
                '_id' => $offer->getId(),
                'name' => $offer->getName()
            ];
        }

        return $assoc;
    }

    protected function buildMatch(StatisticsFilter $filter)
    {
        $match = [
            'date' => [
                '$gte' => $filter->getDateRange()->getFrom()->format('Y-m-d'),
                '$lte' => $filter->getDateRange()->getTo()->format('Y-m-d'),
            ]
        ];

        if ($filter->getMediaId()) {
            $match['event.mediaId'] = (int)$filter->getMediaId();
        }

        return $match;
    }
}
