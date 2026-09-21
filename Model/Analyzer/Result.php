<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Analyzer;

use HenriqueKieckbusch\Jev\Model\Analysis;

/**
 * Outcome of one Analyzer::analyze() call.
 */
class Result
{
    /**
     * @param Analysis $analysis Stored analysis (fresh or unchanged)
     * @param bool $skipped True when the context did not change and no API call was made
     */
    public function __construct(
        private readonly Analysis $analysis,
        private readonly bool $skipped
    ) {
    }

    /**
     * The stored analysis, fresh or unchanged.
     *
     * @return Analysis
     */
    public function getAnalysis(): Analysis
    {
        return $this->analysis;
    }

    /**
     * True when the context did not change and no API call was made.
     *
     * @return bool
     */
    public function isSkipped(): bool
    {
        return $this->skipped;
    }
}
