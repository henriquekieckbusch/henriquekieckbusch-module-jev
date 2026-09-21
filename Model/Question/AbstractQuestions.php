<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Question;

use HenriqueKieckbusch\Jev\Model\Question;

/**
 * Builds Question objects lazily from the definitions returned by getDefinitions().
 */
abstract class AbstractQuestions implements QuestionsInterface
{
    /**
     * @var Question[]|null
     */
    private ?array $questions = null;

    /**
     * Raw definitions: [code => [label, instructions, options]].
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, string|null>}>
     */
    abstract protected function getDefinitions(): array;

    /**
     * @inheritdoc
     */
    public function getQuestions(): array
    {
        if ($this->questions === null) {
            $this->questions = [];
            foreach ($this->getDefinitions() as $code => [$label, $instructions, $options]) {
                $this->questions[$code] = new Question($code, $label, $instructions, $options);
            }
        }
        return array_values($this->questions);
    }

    /**
     * @inheritdoc
     */
    public function getQuestion(string $code): ?Question
    {
        $this->getQuestions();
        return $this->questions[$code] ?? null;
    }
}
