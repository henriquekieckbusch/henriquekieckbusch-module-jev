<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Handler;

use HenriqueKieckbusch\Jev\Model\Question\QuestionsInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Everything the Analyzer needs to know about one entity type (order, customer, product, ...).
 */
interface HandlerInterface
{
    /**
     * Entity type code used in the analysis table and in URLs, e.g. "order".
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Human readable name, e.g. "Order".
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * The Jev questions asked for this entity type.
     *
     * @return QuestionsInterface
     */
    public function getQuestions(): QuestionsInterface;

    /**
     * Build the state (context) sent to Jev for the entity.
     *
     * @param int $entityId
     * @return array<string, mixed>
     * @throws NoSuchEntityException
     */
    public function buildContext(int $entityId): array;

    /**
     * Store the answers on the entity (attributes or columns) without triggering the entity save events.
     *
     * @param int $entityId
     * @param array<string,string> $choices Question code => chosen option code
     * @return void
     */
    public function persist(int $entityId, array $choices): void;
}
