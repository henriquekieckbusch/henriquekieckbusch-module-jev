<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Context;

use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Precomputes elapsed time for the context, because Jev is bad at date arithmetic.
 */
class Age
{
    /**
     * @param DateTime $dateTime
     */
    public function __construct(
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * Whole days between the given UTC datetime and now, or null when the value is empty or invalid.
     *
     * @param string|null $datetime
     * @return int|null
     */
    public function daysSince(?string $datetime): ?int
    {
        $hours = $this->hoursSince($datetime);
        return $hours === null ? null : intdiv($hours, 24);
    }

    /**
     * Whole hours between the given UTC datetime and now, or null when the value is empty or invalid.
     *
     * @param string|null $datetime
     * @return int|null
     */
    public function hoursSince(?string $datetime): ?int
    {
        if ($datetime === null || $datetime === '' || $datetime === '0000-00-00 00:00:00') {
            return null;
        }
        $timestamp = $this->dateTime->gmtTimestamp($datetime);
        if (!$timestamp) {
            return null;
        }
        return max(0, intdiv($this->dateTime->gmtTimestamp() - $timestamp, 3600));
    }

    /**
     * UTC datetime string N days ago, e.g. for "created_at >= ?" filters.
     *
     * @param int $days
     * @return string
     */
    public function daysAgo(int $days): string
    {
        return $this->dateTime->gmtDate(null, $this->dateTime->gmtTimestamp() - $days * 86400);
    }

    /**
     * UTC datetime string N hours ago.
     *
     * @param int $hours
     * @return string
     */
    public function hoursAgo(int $hours): string
    {
        return $this->dateTime->gmtDate(null, $this->dateTime->gmtTimestamp() - $hours * 3600);
    }
}
