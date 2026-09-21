<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Plugin;

use Magento\Review\Model\ResourceModel\Review\Product\Collection;

/**
 * The review grid collection selects an explicit column list from the "review" table
 * (no "rt.*"), so the Jev columns added by AddReviewGridColumns::execute() need to be
 * selected here, right before the query runs.
 */
class AddReviewGridSelectColumns
{
    private const COLUMNS = ['jev_review_action', 'jev_review_authenticity'];

    /**
     * Add the Jev columns to the review grid select before it loads.
     *
     * @param Collection $subject
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array{0: bool, 1: bool}
     */
    public function beforeLoad(Collection $subject, $printQuery = false, $logQuery = false): array
    {
        if (!$subject->isLoaded()) {
            $columns = [];
            foreach (self::COLUMNS as $column) {
                $columns[$column] = 'rt.' . $column;
            }
            $subject->getSelect()->columns($columns);
        }
        return [$printQuery, $logQuery];
    }
}
