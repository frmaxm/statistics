<?php

namespace StatisticBundle\Document\Repository;

use Irev\MainBundle\Document\Repository\DocumentRepository;
use Irev\MainBundle\Entity\FilteredRepositoryInterface;
use StatisticBundle\Filter\StatisticsFilter;

class StatisticsUsersRepository extends DocumentRepository implements FilteredRepositoryInterface
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

        return $qb;
    }
}
