<?php

namespace StatisticBundle\Controller;

use AdminBundle\Controller\BaseController;
use AdminBundle\Security\Annotation\ActionAccess;
use AdminBundle\Security\Annotation\ControllerAccess;
use StatisticBundle\Document\StatisticsApps;
use StatisticBundle\Document\StatisticsPlayers;
use StatisticBundle\Filter\StatisticsFilter;
use StatisticBundle\Form\Type\StatisticsFilterType;
use StatisticBundle\Form\Type\StatisticsPlayerFilterType;
use StatisticBundle\Manager\StatisticsManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * @ControllerAccess()
 */
class StatisticsController extends BaseController
{
    /**
     * @ActionAccess(title="Статистика пользователей")
     */
    public function indexAction(Request $request)
    {
        $filter = new StatisticsFilter();
        $filter->getDateRange()
            ->setFrom(new \DateTime('-1 week'))
        ;

        $form = $this->createFilterForm(StatisticsFilterType::class, $filter);
        $form->handleRequest($request);

        /** @var StatisticsManager $manager */
        $manager = $this->container->get(StatisticsManager::class);
        $data = $manager->get($filter);

        return $this->render('@Statistic/Statistics/index.html.twig', [
            'form' => $form->createView(),
            'data' => $data
        ]);
    }

    /**
     * @ActionAccess(title="Статистика запуска приложения")
     */
    public function appAction(Request $request)
    {
        $filter = new StatisticsFilter();
        $filter
            ->getDateRange()
            ->setFrom(new \DateTime('-1 week'))
        ;

        $form = $this->createFilterForm(StatisticsFilterType::class, $filter);
        $form->handleRequest($request);

        $dm = $this->container->getDocumentManager();
        $qb = $dm->getRepository(StatisticsApps::class)->createFilteredQB($filter);

        $pagination = $this->paginate($qb, 30, [
            'defaultSortFieldName' => 'date',
            'defaultSortDirection' => 'DESC'
        ]);

        /** @var StatisticsManager $manager */
        $manager = $this->container->get(StatisticsManager::class);

        $data['total'] = $manager->getTotalRunApps($filter);
        $data['items'] =  $pagination->getItems();
        foreach ($pagination->getItems() as $item) {
            /** @var StatisticsApps $item */
            $data['all'] += $item->getData()->getRunAll();
            $data['new'] += $item->getNew();
            $data['unique'] += $item->getData()->getRunUnique();
        }

        return $this->render('@Statistic/Statistics/app.html.twig', [
            'data' => $data,
            'pagination' => $pagination,
            'form' => $form->createView()
        ]);
    }

    /**
     * @ActionAccess(title="Статистика запуска плеера")
     */
    public function playerAction(Request $request)
    {
        $filter = new StatisticsFilter();
        $filter->getDateRange()
            ->setFrom(new \DateTime('-1 week'))
        ;

        $form = $this->createFilterForm(StatisticsPlayerFilterType::class, $filter);
        $form->handleRequest($request);

        $qb = $this->container->getMongoRepository(StatisticsPlayers::class)->createFilteredQB($filter);

        $pagination = $this->paginate($qb, 30, [
            'defaultSortFieldName' => 'date',
            'defaultSortDirection' => 'DESC'
        ]);

        /** @var StatisticsManager $manager */
        $manager = $this->container->get(StatisticsManager::class);
        $data['total'] = $manager->getTotalRunPlayers($filter);
        $data['items'] =  $pagination->getItems();

        foreach ($pagination->getItems() as $item) {
            /** @var StatisticsApps $item */
            $data['all'] += $item->getData()->getRunAll();
            $data['unique'] += $item->getData()->getRunUnique();
        }

        return $this->render('@Statistic/Statistics/player.html.twig', [
            'data' => $data,
            'pagination' => $pagination,
            'form' => $form->createView()
        ]);
    }
}
