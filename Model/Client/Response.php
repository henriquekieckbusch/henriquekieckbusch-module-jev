<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Client;

/**
 * Parsed answer of one Typesafe "systemone" call.
 */
class Response
{
    /**
     * @param string $model Model that produced the answers, e.g. "jev-1.13.0"
     * @param array<string,array{choice:string,confidence:float,probabilities:array<string,float>}> $answers
     * @param int $inputTokens
     * @param int $outputTokens
     */
    public function __construct(
        private readonly string $model,
        private readonly array $answers,
        private readonly int $inputTokens,
        private readonly int $outputTokens
    ) {
    }

    /**
     * Model that produced the answers, e.g. "jev-1.13.0".
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * All answers keyed by question code.
     *
     * @return array<string, array{choice: string, confidence: float, probabilities: array<string, float>}>
     */
    public function getAnswers(): array
    {
        return $this->answers;
    }

    /**
     * Chosen option for a question, or null when the question was not answered.
     *
     * @param string $code
     * @return string|null
     */
    public function getChoice(string $code): ?string
    {
        return $this->answers[$code]['choice'] ?? null;
    }

    /**
     * Chosen option of every answered question: question code => option code.
     *
     * @return array<string, string>
     */
    public function getChoices(): array
    {
        $choices = [];
        foreach ($this->answers as $code => $answer) {
            $choices[$code] = $answer['choice'];
        }
        return $choices;
    }

    /**
     * Number of input tokens billed for the call.
     *
     * @return int
     */
    public function getInputTokens(): int
    {
        return $this->inputTokens;
    }

    /**
     * Number of output tokens billed for the call.
     *
     * @return int
     */
    public function getOutputTokens(): int
    {
        return $this->outputTokens;
    }
}
