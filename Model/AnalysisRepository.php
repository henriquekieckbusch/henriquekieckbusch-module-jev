<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model;

use HenriqueKieckbusch\Jev\Model\ResourceModel\Analysis as AnalysisResource;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * Loads and saves Analysis rows by entity type and id.
 */
class AnalysisRepository
{
    /**
     * @param AnalysisFactory $analysisFactory
     * @param AnalysisResource $resource
     */
    public function __construct(
        private readonly AnalysisFactory $analysisFactory,
        private readonly AnalysisResource $resource
    ) {
    }

    /**
     * Existing analysis of the entity, or null when it was never analyzed.
     *
     * @param string $entityType
     * @param int $entityId
     * @return Analysis|null
     */
    public function getByEntity(string $entityType, int $entityId): ?Analysis
    {
        $analysis = $this->analysisFactory->create();
        $this->resource->loadByEntity($analysis, $entityType, $entityId);
        return $analysis->getId() ? $analysis : null;
    }

    /**
     * Existing analysis or a new, unsaved one for the entity.
     *
     * @param string $entityType
     * @param int $entityId
     * @return Analysis
     */
    public function getOrCreate(string $entityType, int $entityId): Analysis
    {
        $analysis = $this->getByEntity($entityType, $entityId);
        if ($analysis === null) {
            $analysis = $this->analysisFactory->create();
            $analysis->setData(Analysis::ENTITY_TYPE, $entityType);
            $analysis->setData(Analysis::ENTITY_ID, $entityId);
        }
        return $analysis;
    }

    /**
     * Persist the analysis row.
     *
     * @param Analysis $analysis
     * @return void
     * @throws AlreadyExistsException
     */
    public function save(Analysis $analysis): void
    {
        $this->resource->save($analysis);
    }

    /**
     * Ids of the given list that already have a successful analysis.
     *
     * @param string $entityType
     * @param int[] $entityIds
     * @return int[]
     */
    public function filterAnalyzed(string $entityType, array $entityIds): array
    {
        return $this->resource->getAnalyzedIds($entityType, $entityIds);
    }
}
