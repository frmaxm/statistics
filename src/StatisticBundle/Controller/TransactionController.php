<?php

namespace StatisticBundle\Controller;

use AdminBundle\Controller\BaseController;
use AdminBundle\Security\Annotation\ActionAccess;
use AdminBundle\Security\Annotation\ControllerAccess;
use PhpOffice\PhpSpreadsheet\Exception;
use StatisticBundle\Filter\StatisticsFilter;
use StatisticBundle\Form\Type\StatisticsFilterType;
use StatisticBundle\Form\Type\StatisticsPPVFilterType;
use StatisticBundle\Form\Type\StatisticsSubscriptionsFilterType;
use StatisticBundle\Manager\StatisticsTransactionCoinsManager;
use StatisticBundle\Manager\StatisticsTransactionPPVManager;
use StatisticBundle\Manager\StatisticsTransactionSubscriptionsManager;
use StatisticBundle\Service\SubscriptionStatisticExport\SubscriptionStatisticExportHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @ControllerAccess()
 */
class TransactionController extends BaseController
{
    private const SORT_BY_TOTAL_SUM_DESC = 'total_desc';
    private const SORT_BY_OFFER_NAME_ASC = 'name_asc';

    /**
     * @ActionAccess(title="Статистика по мячикам")
     */
    public function coinsAction(Request $request)
    {
        $filter = new StatisticsFilter();

        $form = $this->createFilterForm(StatisticsFilterType::class, $filter);
        $form->handleRequest($request);

        /** @var StatisticsTransactionCoinsManager $manager */
        $manager = $this->container->get(StatisticsTransactionCoinsManager::class);

        $result = $manager->getStatistics($filter);

        return $this->render('@Statistic/StatisticsTransaction/coins.html.twig', [
            'form' => $form->createView(),
            'result' => $result
        ]);
    }

    /**
     * @ActionAccess(title="Статистика по ППВ")
     */
    public function ppvAction(Request $request)
    {
        $filter = new StatisticsFilter();

        $form = $this->createFilterForm(StatisticsPPVFilterType::class, $filter);
        $form->handleRequest($request);

        /** @var StatisticsTransactionPPVManager $manager */
        $manager = $this->container->get(StatisticsTransactionPPVManager::class);

        $result = $manager->getStatistics($filter);

        return $this->render('@Statistic/StatisticsTransaction/ppv.html.twig', [
            'form' => $form->createView(),
            'result' => $result
        ]);
    }

    /**
     * @ActionAccess(title="Статистика по подпискам")
     */
    public function subscriptionsAction(Request $request)
    {
        $filter = new StatisticsFilter();

        $form = $this->createFilterForm(StatisticsSubscriptionsFilterType::class, $filter);
        $form->handleRequest($request);

        /** @var StatisticsTransactionSubscriptionsManager $manager */
        $manager = $this->container->get(StatisticsTransactionSubscriptionsManager::class);

        $result = $manager->getStatistics($filter);

        if ($filter->getSortBy() === self::SORT_BY_TOTAL_SUM_DESC) {
            uasort(
                $result['data']['byStores'],
                static function ($a, $b) {
                    return $b['sum'] <=> $a['sum'];
                }
            );
        } elseif ($filter->getSortBy() === self::SORT_BY_OFFER_NAME_ASC) {
            uasort(
                $result['data']['byStores'],
                static function ($a, $b) {
                    return $a['offer']['name'] <=> $b['offer']['name'];
                }
            );
        }

        return $this->render('@Statistic/StatisticsTransaction/subscriptions.html.twig', [
            'form' => $form->createView(),
            'result' => $result
        ]);
    }

    /**
     * @param Request $request
     * @param SubscriptionStatisticExportHandler $handler
     *
     * @return StreamedResponse
     *
     * @throws Exception
     */
    public function exportAction(Request $request, SubscriptionStatisticExportHandler $handler): StreamedResponse
    {
        ['writer' => $writer, 'filename' => $filename] = $handler->handle($request);

        $response =  new StreamedResponse(
            static function () use ($writer) {
                $writer->save('php://output');
            }
        );

        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment;' . $filename);
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
