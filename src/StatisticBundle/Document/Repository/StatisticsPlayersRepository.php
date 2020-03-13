<?php

namespace StatisticBundle\Document\Repository;

use Irev\MainBundle\Document\Repository\DocumentRepository;
use Irev\MainBundle\Entity\FilteredRepositoryInterface;
use StatisticBundle\Filter\StatisticsFilter;

class StatisticsPlayersRepository extends DocumentRepository implements FilteredRepositoryInterface
{
    public function createFilteredQB($filter)
    {
        if (!$filter instanceof StatisticsFilter) {
            throw new \LogicException();
        }

        $qb = $this->createQueryBuilder();

        if ($filter->getDateRange()) {
            $qb->field('date')
                ->gte($filter->getDateRange()->getFrom())
                ->lte($filter->getDateRange()->getTo())
            ;
        }

        if ($filter->getChannel()) {
            $qb->field('channel')->references($filter->getChannel());
        }

        return $qb;
    }
}
