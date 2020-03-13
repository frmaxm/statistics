<?php

declare(strict_types=1);

namespace StatisticBundle\Service\SubscriptionStatisticExport;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriptionStatisticExportHandler
{
    private const TABLE_HEADER_END_INDEX = 3;
    private const GOOGLE_PLATFORM = 'google';
    private const APPLE_PLATFORM = 'apple';
    private const SMART_DEVICE = 'smart';
    private const MOBILE_DEVICE = 'mobile';
    private const PRODUCT_ALL = 'all';
    private const BASE_FILENAME = 'subscription_statistic';

    /**
     * @var SubscriptionStatisticExportManager
     */
    private $manager;

    public function __construct(SubscriptionStatisticExportManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param Request $request
     *
     * @return array
     *
     * @throws Exception
     */
    public function handle(Request $request): array
    {
        $data = json_decode($request->get('data'), true);

        $spreadsheet = new Spreadsheet();

        /** @var Worksheet $activeSheet */
        $activeSheet = $spreadsheet->getActiveSheet();

        $index = self::TABLE_HEADER_END_INDEX;

        $offersCount = count($data['data']['byStores']);

        $this->addTableHeader($activeSheet);
        $this->initTableBody($activeSheet, $index, $offersCount);
        $this->fillTableBody($activeSheet, $data, $index);
        $this->initTableFooter($activeSheet, $index, $offersCount);
        $this->fillTableFooter($activeSheet, $data['total'], $index, $offersCount);

        $writer = new Xlsx($spreadsheet);

        $uri = parse_url($request->get('uri'));

        $query = $uri['query'] ?? null;

        $filename = $this->buildFilename($query);

        return ['writer' => $writer, 'filename' => $filename];
    }

    /**
     * @param Worksheet $activeSheet
     *
     * @throws Exception
     */
    private function addTableHeader(Worksheet $activeSheet): void
    {
        $activeSheet->getColumnDimension('A')->setWidth(45);
        $activeSheet->mergeCells('A1:A3')->setCellValue('A1', 'Offer');
        $activeSheet->getStyle('A1')->getAlignment()->setHorizontal('center')->setVertical('center');

        $activeSheet->mergeCells('B1:G1')->setCellValue('B1', 'Apple');
        $activeSheet->getStyle('B1')->getAlignment()->setHorizontal('center');
        $activeSheet->mergeCells('B2:D2')->setCellValue('B2', 'Mobile');
        $activeSheet->getStyle('B2')->getAlignment()->setHorizontal('center');
        $activeSheet->mergeCells('E2:G2')->setCellValue('E2', 'Smart');
        $activeSheet->getStyle('E2')->getAlignment()->setHorizontal('center');
        $activeSheet->setCellValue('B3', 'Покупок');
        $activeSheet->mergeCells('C3:D3')->setCellValue('C3', 'Сумма');
        $activeSheet->getStyle('C3')->getAlignment()->setHorizontal('center');
        $activeSheet->setCellValue('E3', 'Покупок');
        $activeSheet->mergeCells('F3:G3')->setCellValue('F3', 'Сумма');
        $activeSheet->getStyle('F3')->getAlignment()->setHorizontal('center');

        $activeSheet->mergeCells('H1:M1')->setCellValue('H1', 'Google');
        $activeSheet->getStyle('H1')->getAlignment()->setHorizontal('center');
        $activeSheet->mergeCells('H2:J2')->setCellValue('H2', 'Mobile');
        $activeSheet->getStyle('H2')->getAlignment()->setHorizontal('center');
        $activeSheet->mergeCells('K2:M2')->setCellValue('K2', 'Smart');
        $activeSheet->getStyle('K2')->getAlignment()->setHorizontal('center');
        $activeSheet->setCellValue('H3', 'Покупок');
        $activeSheet->mergeCells('I3:J3')->setCellValue('I3', 'Сумма');
        $activeSheet->getStyle('I3')->getAlignment()->setHorizontal('center');
        $activeSheet->setCellValue('K3', 'Покупок');
        $activeSheet->mergeCells('L3:M3')->setCellValue('L3', 'Сумма');
        $activeSheet->getStyle('L3')->getAlignment()->setHorizontal('center');

        $activeSheet->mergeCells('N1:P2')->setCellValue('N1', 'New');
        $activeSheet->getStyle('N1')->getAlignment()->setHorizontal('center');
        $activeSheet->setCellValue('N3', 'Покупок');
        $activeSheet->mergeCells('O3:P3')->setCellValue('O3', 'Сумма');
        $activeSheet->getStyle('O3')->getAlignment()->setHorizontal('center');

        $activeSheet->mergeCells('Q1:S2')->setCellValue('Q1', 'Renewal');
        $activeSheet->getStyle('Q1')->getAlignment()->setHorizontal('center');
        $activeSheet->setCellValue('Q3', 'Покупок');
        $activeSheet->mergeCells('R3:S3')->setCellValue('R3', 'Сумма');
        $activeSheet->getStyle('R3')->getAlignment()->setHorizontal('center');

        $activeSheet->mergeCells('T1:V2')->setCellValue('T1', 'Total');
        $activeSheet->getStyle('T1')->getAlignment()->setHorizontal('center');
        $activeSheet->setCellValue('T3', 'Покупок');
        $activeSheet->mergeCells('U3:V3')->setCellValue('U3', 'Сумма');
        $activeSheet->getStyle('U3')->getAlignment()->setHorizontal('center');
    }

    /**
     * @param Worksheet $activeSheet
     * @param int $index
     * @param int $offersCount
     *
     * @throws Exception
     */
    private function initTableBody(Worksheet $activeSheet, int $index, int $offersCount): void
    {
        $startIndex = ++$index;

        for ($i = $startIndex; $i < $offersCount + $startIndex; $i++) {
            $this->initCells($activeSheet, $i);
        }
    }

    /**
     * @param array $statisticData
     * @param int $index
     * @param Worksheet $activeSheet
     *
     * @throws Exception
     */
    private function fillTableBody(Worksheet $activeSheet, array $statisticData, int $index): void
    {
        foreach ($statisticData['data']['byStores'] as $item) {
            $index = ++$index;

            //total
            $activeSheet->setCellValue(sprintf('T%s', $index), $item['count']);
            $activeSheet->setCellValue(sprintf('U%s', $index), $item['sum']);

            foreach ($statisticData['data']['byPurchaseType'] as $sku => $skuData) {
                if ($sku === $item['sku']) {
                    foreach ($skuData['purchaseTypes'] as $purchaseType => $purchaseData) {
                        if ($purchaseType === 'renewal') {
                            //renewal
                            $activeSheet->setCellValue(sprintf('Q%s', $index), $purchaseData['count']);
                            $activeSheet->setCellValue(sprintf('R%s', $index), $purchaseData['sum']);
                        } else {
                            //new
                            $activeSheet->setCellValue(sprintf('N%s', $index), $purchaseData['count']);
                            $activeSheet->setCellValue(sprintf('O%s', $index), $purchaseData['sum']);
                        }
                    }
                }
            }

            $activeSheet->getColumnDimension(sprintf('A%s', $index))->setWidth(45);
            $activeSheet->setCellValue(sprintf('A%s', $index), $item['offer']['name']);
            $activeSheet->getStyle(sprintf('A%s', $index))->getFont()->setSize(10);

            foreach ($item['stores'] as $platform => $platformData) {
                foreach ($platformData['devices'] as $deviceType => $statistic) {
                    if ($deviceType === self::MOBILE_DEVICE && $platform === self::APPLE_PLATFORM) {
                        $activeSheet->setCellValue(sprintf('B%s', $index), $statistic['count']);
                        $activeSheet->setCellValue(sprintf('C%s', $index), $statistic['sum']);
                    }

                    if ($deviceType === self::MOBILE_DEVICE && $platform === self::GOOGLE_PLATFORM) {
                        $activeSheet->setCellValue(sprintf('H%s', $index), $statistic['count']);
                        $activeSheet->setCellValue(sprintf('I%s', $index), $statistic['sum']);
                    }

                    if ($deviceType === self::SMART_DEVICE && $platform === self::APPLE_PLATFORM) {
                        $activeSheet->setCellValue(sprintf('E%s', $index), $statistic['count']);
                        $activeSheet->setCellValue(sprintf('F%s', $index), $statistic['sum']);
                    }

                    if ($deviceType === self::SMART_DEVICE && $platform === self::GOOGLE_PLATFORM) {
                        $activeSheet->setCellValue(sprintf('K%s', $index), $statistic['count']);
                        $activeSheet->setCellValue(sprintf('L%s', $index), $statistic['sum']);
                    }
                }
            }
        }
    }

    /**
     * @param Worksheet $activeSheet
     * @param int $index
     * @param int $offersCount
     *
     * @throws Exception
     */
    private function initTableFooter(Worksheet $activeSheet, int $index, int $offersCount): void
    {
        $startIndex = $offersCount + $index + 1;

        $this->initCells($activeSheet, $startIndex);

        $activeSheet->getColumnDimension('A')->setWidth(45);
        $activeSheet->setCellValue(sprintf('A%s', $startIndex), 'Итого:');
        $activeSheet
            ->getStyle(sprintf('A%s', $startIndex))
            ->getAlignment()
            ->setHorizontal('right')
            ->setVertical('center')
        ;
    }

    /**
     * @param Worksheet $activeSheet
     * @param int $index
     *
     * @throws Exception
     */
    private function initCells(Worksheet $activeSheet, int $index): void
    {
        //apple mobile
        $activeSheet->setCellValue(sprintf('B%s', $index), 0);
        $activeSheet->mergeCells(sprintf('C%s:D%s', $index, $index))->setCellValue(sprintf('C%s', $index), 0);
        $activeSheet->getStyle(sprintf('B%s', $index))->getAlignment()->setHorizontal('center');
        $activeSheet->getStyle(sprintf('C%s', $index))->getAlignment()->setHorizontal('center');

        //apple smart
        $activeSheet->setCellValue(sprintf('E%s', $index), 0);
        $activeSheet->mergeCells(sprintf('F%s:G%s', $index, $index))->setCellValue(sprintf('F%s', $index), 0);
        $activeSheet->getStyle(sprintf('E%s', $index))->getAlignment()->setHorizontal('center');
        $activeSheet->getStyle(sprintf('F%s', $index))->getAlignment()->setHorizontal('center');

        //google mobile
        $activeSheet->setCellValue(sprintf('H%s', $index), 0);
        $activeSheet->mergeCells(sprintf('I%s:J%s', $index, $index))->setCellValue(sprintf('I%s', $index), 0);
        $activeSheet->getStyle(sprintf('H%s', $index))->getAlignment()->setHorizontal('center');
        $activeSheet->getStyle(sprintf('I%s', $index))->getAlignment()->setHorizontal('center');

        //google smart
        $activeSheet->setCellValue(sprintf('K%s', $index), 0);
        $activeSheet->mergeCells(sprintf('L%s:M%s', $index, $index))->setCellValue(sprintf('L%s', $index), 0);
        $activeSheet->getStyle(sprintf('K%s', $index))->getAlignment()->setHorizontal('center');
        $activeSheet->getStyle(sprintf('L%s', $index))->getAlignment()->setHorizontal('center');

        //new
        $activeSheet->setCellValue(sprintf('N%s', $index), 0);
        $activeSheet->mergeCells(sprintf('O%s:P%s', $index, $index))->setCellValue(sprintf('O%s', $index), 0);
        $activeSheet->getStyle(sprintf('N%s', $index))->getAlignment()->setHorizontal('center');
        $activeSheet->getStyle(sprintf('O%s', $index))->getAlignment()->setHorizontal('center');

        //renewal
        $activeSheet->setCellValue(sprintf('Q%s', $index), 0);
        $activeSheet->mergeCells(sprintf('R%s:S%s', $index, $index))->setCellValue(sprintf('R%s', $index), 0);
        $activeSheet->getStyle(sprintf('R%s', $index))->getAlignment()->setHorizontal('center');
        $activeSheet->getStyle(sprintf('Q%s', $index))->getAlignment()->setHorizontal('center');

        //total
        $activeSheet->setCellValue(sprintf('T%s', $index), 0);
        $activeSheet->mergeCells(sprintf('U%s:V%s', $index, $index))->setCellValue(sprintf('U%s', $index), 0);
        $activeSheet->getStyle(sprintf('U%s', $index))->getAlignment()->setHorizontal('center');
        $activeSheet->getStyle(sprintf('T%s', $index))->getAlignment()->setHorizontal('center');
    }

    /**
     * @param Worksheet $activeSheet
     * @param array $footerData
     * @param int $index
     * @param int $offersCount
     */
    private function fillTableFooter(Worksheet $activeSheet, array $footerData, int $index, int $offersCount): void
    {
        $startIndex = $offersCount + $index + 1;

        $dataByStores = $footerData['byStores'][0];
        $dataByPurchaseType = $footerData['byPurchaseType'][0];

        //apple mobile
        $activeSheet->setCellValue(
            sprintf('B%s', $startIndex),
            $dataByStores['stores'][self::APPLE_PLATFORM]['devices'][self::MOBILE_DEVICE]['count']
        );
        $activeSheet->setCellValue(
            sprintf('C%s', $startIndex),
            $dataByStores['stores'][self::APPLE_PLATFORM]['devices'][self::MOBILE_DEVICE]['sum']
        );

        //apple smart
        $activeSheet->setCellValue(
            sprintf('E%s', $startIndex),
            $dataByStores['stores'][self::APPLE_PLATFORM]['devices'][self::SMART_DEVICE]['count']
        );
        $activeSheet->setCellValue(
            sprintf('F%s', $startIndex),
            $dataByStores['stores'][self::APPLE_PLATFORM]['devices'][self::SMART_DEVICE]['sum']
        );

        //google mobile
        $activeSheet->setCellValue(
            sprintf('H%s', $startIndex),
            $dataByStores['stores'][self::GOOGLE_PLATFORM]['devices'][self::MOBILE_DEVICE]['count']
        );
        $activeSheet->setCellValue(
            sprintf('I%s', $startIndex),
            $dataByStores['stores'][self::GOOGLE_PLATFORM]['devices'][self::MOBILE_DEVICE]['sum']
        );

        //google smart
        $activeSheet->setCellValue(
            sprintf('K%s', $startIndex),
            $dataByStores['stores'][self::GOOGLE_PLATFORM]['devices'][self::SMART_DEVICE]['count']
        );
        $activeSheet->setCellValue(
            sprintf('L%s', $startIndex),
            $dataByStores['stores'][self::GOOGLE_PLATFORM]['devices'][self::SMART_DEVICE]['sum']
        );

        //new
        $activeSheet->setCellValue(
            sprintf('N%s', $startIndex),
            $dataByPurchaseType['purchaseTypes']['new']['count']
        );
        $activeSheet->setCellValue(
            sprintf('O%s', $startIndex),
            $dataByPurchaseType['purchaseTypes']['new']['sum']
        );

        //renewal
        $activeSheet->setCellValue(
            sprintf('Q%s', $startIndex),
            $dataByPurchaseType['purchaseTypes']['renewal']['count']
        );
        $activeSheet->setCellValue(
            sprintf('R%s', $startIndex),
            $dataByPurchaseType['purchaseTypes']['renewal']['sum']
        );

        //total
        $activeSheet->setCellValue(
            sprintf('T%s', $startIndex),
            $dataByStores['count']
        );
        $activeSheet->setCellValue(
            sprintf('U%s', $startIndex),
            $dataByStores['sum']
        );
    }

    private function buildFilename(?string $filterParams): string
    {
        if (null === $filterParams) {
            return sprintf(
                'filename=%s_%s-%s_product=%s_%s.xls',
                self::BASE_FILENAME,
                date('Y-m-d'),
                date('Y-m-d'),
                self::PRODUCT_ALL,
                date('His')
            );
        }

        parse_str($filterParams, $params);

        $dateRange = str_replace(' ', '', $params['dateRange']);

        $product = $params['product'];

        $productName = empty($product) ? self::PRODUCT_ALL : $this->manager->getProductName($product);

        return sprintf(
            'filename=%s_%s_product=%s_%s.xls',
            self::BASE_FILENAME,
            $dateRange,
            $productName,
            date('His')
        );
    }
}
