<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Observer;

use HenriqueKieckbusch\Jev\Model\Analyzer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Runs a Jev analysis right after an entity is saved and the transaction commits.
 * One instance per entity type, configured in etc/events.xml with the "type" and
 * "event_data_key" arguments.
 */
class AnalyzeOnSave implements ObserverInterface
{
    /**
     * @param Analyzer $analyzer
     * @param string $type Jev entity type, e.g. "order"
     * @param string $eventDataKey Key of the saved model inside the event data
     */
    public function __construct(
        private readonly Analyzer $analyzer,
        private readonly string $type,
        private readonly string $eventDataKey
    ) {
    }

    /**
     * Analyze the saved entity carried by the event, if it resolves to a valid id.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $entity = $observer->getEvent()->getData($this->eventDataKey);
        $entityId = $this->resolveEntityId($entity);
        if ($entityId > 0) {
            $this->analyzer->tryAnalyze($this->type, $entityId);
        }
    }

    /**
     * Resolve the entity id from the saved model, if possible.
     *
     * @param mixed $entity
     * @return int
     */
    private function resolveEntityId(mixed $entity): int
    {
        if ($entity instanceof AbstractModel) {
            return (int)$entity->getId();
        }
        if (is_object($entity) && method_exists($entity, 'getId')) {
            return (int)$entity->getId();
        }
        return 0;
    }
}
