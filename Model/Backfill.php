<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

/**
 * Analyzes the most recent orders and customers that were never analyzed.
 * Runs once when a new API key is saved with Jev enabled.
 */
class Backfill
{
    public const LIMIT = 10;

    /**
     * @param Analyzer $analyzer
     * @param AnalysisRepository $analysisRepository
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param CustomerCollectionFactory $customerCollectionFactory
     */
    public function __construct(
        private readonly Analyzer $analyzer,
        private readonly AnalysisRepository $analysisRepository,
        private readonly OrderCollectionFactory $orderCollectionFactory,
        private readonly CustomerCollectionFactory $customerCollectionFactory
    ) {
    }

    /**
     * Analyze up to LIMIT never-analyzed orders and customers, newest first.
     *
     * @return array{order: int, customer: int} Number of entities analyzed per type
     */
    public function run(): array
    {
        return [
            'order' => $this->analyzeMissing('order', $this->getLatestOrderIds()),
            'customer' => $this->analyzeMissing('customer', $this->getLatestCustomerIds()),
        ];
    }

    /**
     * Analyze up to LIMIT of the given candidates that have no analysis yet.
     *
     * @param string $type
     * @param int[] $candidateIds Newest first
     * @return int
     */
    private function analyzeMissing(string $type, array $candidateIds): int
    {
        $analyzed = $this->analysisRepository->filterAnalyzed($type, $candidateIds);
        $count = 0;
        foreach (array_diff($candidateIds, $analyzed) as $entityId) {
            if ($count >= self::LIMIT) {
                break;
            }
            if ($this->analyzer->tryAnalyze($type, (int)$entityId) !== null) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Ids of the most recent orders (a generous window so LIMIT never-analyzed ones can be found).
     *
     * @return int[]
     */
    private function getLatestOrderIds(): array
    {
        $collection = $this->orderCollectionFactory->create();
        $collection->setOrder('entity_id', 'DESC')->setPageSize(self::LIMIT * 10);
        return array_map('intval', $collection->getAllIds());
    }

    /**
     * Ids of the most recent customers (a generous window so LIMIT never-analyzed ones can be found).
     *
     * @return int[]
     */
    private function getLatestCustomerIds(): array
    {
        $collection = $this->customerCollectionFactory->create();
        $collection->setOrder('entity_id', 'DESC')->setPageSize(self::LIMIT * 10);
        return array_map('intval', $collection->getAllIds());
    }
}
