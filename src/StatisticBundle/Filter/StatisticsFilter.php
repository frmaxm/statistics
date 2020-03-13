<?php

namespace StatisticBundle\Filter;

use Irev\MainBundle\Document\Channel;
use Irev\MainBundle\Document\Product;
use Irev\MainBundle\Filter\DateRange;

class StatisticsFilter
{
    /**
     * @var DateRange
     */
    private $dateRange;

    /**
     * @var Channel
     */
    private $channel;

    private $mediaId;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var string|null
     */
    private $sortBy;

    public function __construct()
    {
        $this->dateRange = new DateRange();
    }

    public function getDateRange()
    {
        return $this->dateRange;
    }

    public function setDateRange($dateRange)
    {
        $this->dateRange = $dateRange;
        $this->dateRange->getFrom()->setTime(0, 0, 0);
        $this->dateRange->getTo()->setTime(23, 59, 59);
        return $this;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function setChannel($channel)
    {
        $this->channel = $channel;
        return $this;
    }

    public function getMediaId()
    {
        return $this->mediaId;
    }

    public function setMediaId($mediaId)
    {
        $this->mediaId = $mediaId;
        return $this;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function setProduct($product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }

    /**
     * @param string $sortBy
     *
     * @return StatisticsFilter
     */
    public function setSortBy(string $sortBy): self
    {
        $this->sortBy = $sortBy;
        return $this;
    }
}
