<?php

declare(strict_types=1);

namespace StatisticBundle\Service\SubscriptionStatisticExport;

use Doctrine\ODM\MongoDB\DocumentManager;
use Irev\MainBundle\Document\Product;

class SubscriptionStatisticExportManager
{
    /**
     * @var DocumentManager
     */
    protected $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function getProductName(string $id): string
    {
        $product = $this->dm
            ->getRepository(Product::class)
            ->findOneBy(['_id' => $id])
        ;

        return $product ? $product->getName() : 'Not found';
    }
}
