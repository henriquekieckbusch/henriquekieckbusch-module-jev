<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Resource model of henriquekieckbusch_jev_analysis.
 */
class Analysis extends AbstractDb
{
    public const TABLE = 'henriquekieckbusch_jev_analysis';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE, 'analysis_id');
    }

    /**
     * Load the analysis of one entity, if any.
     *
     * @param AbstractModel $object
     * @param string $entityType
     * @param int $entityId
     * @return void
     */
    public function loadByEntity(AbstractModel $object, string $entityType, int $entityId): void
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('entity_type = ?', $entityType)
            ->where('entity_id = ?', $entityId)
            ->limit(1);
        $data = $connection->fetchRow($select);
        if ($data) {
            $object->setData($data);
        }
        $this->_afterLoad($object);
    }

    /**
     * Ids (of the given entity type) that already have a successful analysis.
     *
     * @param string $entityType
     * @param int[] $entityIds
     * @return int[]
     */
    public function getAnalyzedIds(string $entityType, array $entityIds): array
    {
        if ($entityIds === []) {
            return [];
        }
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), 'entity_id')
            ->where('entity_type = ?', $entityType)
            ->where('entity_id IN (?)', $entityIds)
            ->where('analyzed_at IS NOT NULL');
        return array_map('intval', $connection->fetchCol($select));
    }
}
