<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model;

use HenriqueKieckbusch\Jev\Model\Client\Response;
use HenriqueKieckbusch\Jev\Model\ResourceModel\Analysis as AnalysisResource;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;

/**
 * One row of henriquekieckbusch_jev_analysis: the last Jev analysis of one entity,
 * including every answer with its confidence and probabilities.
 */
class Analysis extends AbstractModel
{
    public const ENTITY_TYPE = 'entity_type';
    public const ENTITY_ID = 'entity_id';
    public const CONTEXT_HASH = 'context_hash';
    public const MODEL = 'model';
    public const ANSWERS = 'answers';
    public const INPUT_TOKENS = 'input_tokens';
    public const OUTPUT_TOKENS = 'output_tokens';
    public const ERROR = 'error';
    public const ANALYZED_AT = 'analyzed_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Json $json
     * @param AnalysisResource|null $resource
     * @param AnalysisResource\Collection|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        private readonly Json $json,
        ?AnalysisResource $resource = null,
        ?AnalysisResource\Collection $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(AnalysisResource::class);
    }

    /**
     * Jev entity type, e.g. "order".
     *
     * @return string
     */
    public function getEntityType(): string
    {
        return (string)$this->getData(self::ENTITY_TYPE);
    }

    /**
     * Id of the analyzed entity.
     *
     * @return int
     */
    public function getEntityId(): int
    {
        return (int)$this->getData(self::ENTITY_ID);
    }

    /**
     * Hash of the context that was last sent to Jev, or null if never analyzed.
     *
     * @return string|null
     */
    public function getContextHash(): ?string
    {
        return $this->getData(self::CONTEXT_HASH);
    }

    /**
     * Error message of the last failed analysis, or null if it succeeded.
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        $error = $this->getData(self::ERROR);
        return $error === null || $error === '' ? null : (string)$error;
    }

    /**
     * Timestamp of the last analysis, or null if never analyzed.
     *
     * @return string|null
     */
    public function getAnalyzedAt(): ?string
    {
        return $this->getData(self::ANALYZED_AT);
    }

    /**
     * Whether at least one successful analysis is stored.
     *
     * @return bool
     */
    public function hasAnswers(): bool
    {
        return $this->getAnalyzedAt() !== null && $this->getAnswers() !== [];
    }

    /**
     * Decoded answers: question code => [choice, confidence, probabilities].
     *
     * @return array<string, array{choice: string, confidence: float, probabilities: array<string, float>}>
     */
    public function getAnswers(): array
    {
        $raw = $this->getData(self::ANSWERS);
        if ($raw === null || $raw === '') {
            return [];
        }
        try {
            $answers = $this->json->unserialize((string)$raw);
        } catch (\InvalidArgumentException $e) {
            return [];
        }
        return is_array($answers) ? $answers : [];
    }

    /**
     * Encode and store the answers.
     *
     * @param array<string,array{choice:string,confidence:float,probabilities:array<string,float>}> $answers
     * @return $this
     */
    public function setAnswers(array $answers): self
    {
        return $this->setData(self::ANSWERS, $this->json->serialize($answers));
    }

    /**
     * Stored choice for one question code, or null.
     *
     * @param string $code
     * @return string|null
     */
    public function getChoice(string $code): ?string
    {
        return $this->getAnswers()[$code]['choice'] ?? null;
    }

    /**
     * Fill this row with a successful Jev response, clearing any previous error.
     *
     * @param Response $response
     * @param string $contextHash
     * @param string $analyzedAt GMT date string
     * @return $this
     */
    public function recordSuccess(Response $response, string $contextHash, string $analyzedAt): self
    {
        $this->setData(self::CONTEXT_HASH, $contextHash);
        $this->setData(self::MODEL, $response->getModel());
        $this->setAnswers($response->getAnswers());
        $this->setData(self::INPUT_TOKENS, $response->getInputTokens());
        $this->setData(self::OUTPUT_TOKENS, $response->getOutputTokens());
        $this->setData(self::ERROR, null);
        $this->setData(self::ANALYZED_AT, $analyzedAt);
        return $this;
    }

    /**
     * Record a failed analysis attempt, keeping any previous answers untouched.
     *
     * @param string $message
     * @return $this
     */
    public function recordError(string $message): self
    {
        return $this->setData(self::ERROR, mb_substr($message, 0, 1000));
    }
}
