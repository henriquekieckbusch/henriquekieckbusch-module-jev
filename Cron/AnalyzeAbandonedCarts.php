<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Cron;

use HenriqueKieckbusch\Jev\Model\Analyzer;
use HenriqueKieckbusch\Jev\Model\Config;
use HenriqueKieckbusch\Jev\Model\Context\Age;
use HenriqueKieckbusch\Jev\Model\ResourceModel\Analysis as AnalysisResource;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;

/**
 * Hourly job: asks Jev how to recover carts that have been abandoned for at least
 * the configured number of hours and were changed since their last analysis.
 */
class AnalyzeAbandonedCarts
{
    private const MAX_AGE_DAYS = 30;

    /**
     * @param Config $config
     * @param Analyzer $analyzer
     * @param Age $age
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly Config $config,
        private readonly Analyzer $analyzer,
        private readonly Age $age,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * Analyze every abandoned cart eligible for a fresh Jev analysis.
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->config->isReady() || !$this->config->isAbandonedCartEnabled()) {
            return;
        }
        foreach ($this->getQuoteIds() as $quoteId) {
            $this->analyzer->tryAnalyze('quote', $quoteId);
        }
    }

    /**
     * Ids of the abandoned carts eligible for analysis, newest first.
     *
     * Active carts with items and an e-mail, untouched for the minimum age, never analyzed
     * or updated after their last analysis. Limited to the configured batch size.
     *
     * @return int[]
     */
    private function getQuoteIds(): array
    {
        $collection = $this->collectionFactory->create();
        $analysisTable = $collection->getTable(AnalysisResource::TABLE);
        $collection->addFieldToFilter('main_table.is_active', 1)
            ->addFieldToFilter('main_table.items_count', ['gt' => 0])
            ->addFieldToFilter('main_table.customer_email', ['notnull' => true])
            ->addFieldToFilter(
                'main_table.updated_at',
                ['lteq' => $this->age->hoursAgo($this->config->getAbandonedCartMinAgeHours())]
            )
            ->addFieldToFilter('main_table.updated_at', ['gteq' => $this->age->daysAgo(self::MAX_AGE_DAYS)]);
        $collection->getSelect()
            ->joinLeft(
                ['jev' => $analysisTable],
                "jev.entity_type = 'quote' AND jev.entity_id = main_table.entity_id",
                []
            )
            ->where('jev.analysis_id IS NULL OR jev.analyzed_at IS NULL OR jev.analyzed_at < main_table.updated_at')
            ->order('main_table.updated_at DESC')
            ->limit($this->config->getAbandonedCartBatchSize());
        return array_map('intval', $collection->getAllIds());
    }
}
