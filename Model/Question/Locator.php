<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Question;

use HenriqueKieckbusch\Jev\Model\Handler\Pool;
use HenriqueKieckbusch\Jev\Model\Question;

/**
 * Finds a question by entity type and code, or by attribute code alone
 * (used by EAV source models, which only know the attribute code).
 */
class Locator
{
    /**
     * @param Pool $pool
     */
    public function __construct(
        private readonly Pool $pool
    ) {
    }

    /**
     * Find the question for the given entity type and code.
     *
     * @param string $type Entity type, e.g. "product"
     * @param string $code Question code, e.g. "product_health"
     * @return Question|null
     */
    public function get(string $type, string $code): ?Question
    {
        return $this->pool->has($type) ? $this->pool->get($type)->getQuestions()->getQuestion($code) : null;
    }

    /**
     * First question of the given entity type whose attribute code matches, e.g. "jev_product_health".
     *
     * @param string $type
     * @param string $attributeCode
     * @return Question|null
     */
    public function getByAttributeCode(string $type, string $attributeCode): ?Question
    {
        if (strpos($attributeCode, Question::ATTRIBUTE_PREFIX) !== 0) {
            return null;
        }
        return $this->get($type, substr($attributeCode, strlen(Question::ATTRIBUTE_PREFIX)));
    }
}
