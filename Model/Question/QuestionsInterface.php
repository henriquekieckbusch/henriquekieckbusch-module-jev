<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Question;

use HenriqueKieckbusch\Jev\Model\Question;

/**
 * A fixed, ordered set of questions asked about one entity type.
 */
interface QuestionsInterface
{
    /**
     * All questions asked about this entity type, in order.
     *
     * @return Question[] Ordered list of questions
     */
    public function getQuestions(): array;

    /**
     * Find a question by its code.
     *
     * @param string $code Question code (without the "jev_" prefix)
     * @return Question|null
     */
    public function getQuestion(string $code): ?Question;
}
