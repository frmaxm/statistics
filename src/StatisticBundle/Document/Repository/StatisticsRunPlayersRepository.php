<?php

namespace StatisticBundle\Document\Repository;

use Irev\MainBundle\Document\Repository\DocumentRepository;
use Irev\MainBundle\Entity\FilteredRepositoryInterface;

class StatisticsRunPlayersRepository extends DocumentRepository implements FilteredRepositoryInterface
{
    public function createFilteredQB($filter)
    {
        return $this->createQueryBuilder();
    }
}
