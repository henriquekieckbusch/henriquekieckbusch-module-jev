<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Block\Adminhtml;

use HenriqueKieckbusch\Jev\Model\Analysis;
use HenriqueKieckbusch\Jev\Model\AnalysisRepository;
use HenriqueKieckbusch\Jev\Model\Config;
use HenriqueKieckbusch\Jev\Model\Handler\Pool;
use HenriqueKieckbusch\Jev\Model\Question;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Renders the Jev answers of one entity (label, answer, confidence, probabilities)
 * plus the "Refresh Jev" link. Tabs set "entity_type" and "entity_id" on this block.
 */
class Panel extends Template
{
    /**
     * @var string
     */
    protected $_template = 'HenriqueKieckbusch_Jev::panel.phtml';

    /**
     * Cached analysis of the current entity, lazily loaded by getAnalysis().
     *
     * @var Analysis|null
     */
    private ?Analysis $analysis = null;

    /**
     * Whether the analysis cache has been populated yet.
     *
     * @var bool
     */
    private bool $analysisLoaded = false;

    /**
     * @param Context $context
     * @param Config $config
     * @param Pool $pool
     * @param AnalysisRepository $analysisRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly Config $config,
        private readonly Pool $pool,
        private readonly AnalysisRepository $analysisRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Entity type set on this block by the tab, e.g. "order".
     *
     * @return string
     */
    public function getEntityType(): string
    {
        return (string)$this->getData('entity_type');
    }

    /**
     * Entity id set on this block by the tab.
     *
     * @return int
     */
    public function getEntityId(): int
    {
        return (int)$this->getData('entity_id');
    }

    /**
     * Whether Jev is configured and ready to analyze entities.
     *
     * @return bool
     */
    public function isReady(): bool
    {
        return $this->config->isReady();
    }

    /**
     * Load and cache the current entity's analysis, if any.
     *
     * @return Analysis|null
     */
    public function getAnalysis(): ?Analysis
    {
        if (!$this->analysisLoaded) {
            $this->analysis = $this->getEntityId() > 0
                ? $this->analysisRepository->getByEntity($this->getEntityType(), $this->getEntityId())
                : null;
            $this->analysisLoaded = true;
        }
        return $this->analysis;
    }

    /**
     * One row per question, in question order, with tooltip text for the question and every answer.
     *
     * @return array<int,array{label:string,question:string|null,code:string,answer:string|null,
     *     answerDescription:string|null,confidence:int|null,
     *     probabilities:array<int,array{label:string,description:string|null,percent:int}>}>
     */
    public function getRows(): array
    {
        if (!$this->pool->has($this->getEntityType())) {
            return [];
        }
        $answers = $this->getAnalysis() ? $this->getAnalysis()->getAnswers() : [];
        $rows = [];
        foreach ($this->pool->get($this->getEntityType())->getQuestions()->getQuestions() as $question) {
            $answer = $answers[$question->getCode()] ?? null;
            $rows[] = [
                'label' => __($question->getLabel()),
                'question' => $this->translateOrNull($question->getInstructions()),
                'code' => $question->getCode(),
                'answer' => $answer !== null ? __($question->getOptionLabel($answer['choice'])) : null,
                'answerDescription' => $answer !== null
                    ? $this->translateOrNull($question->getOptionDescription($answer['choice']))
                    : null,
                'confidence' => $answer !== null ? (int)round($answer['confidence'] * 100) : null,
                'probabilities' => $answer !== null ? $this->getProbabilityRows($question, $answer) : [],
            ];
        }
        return $rows;
    }

    /**
     * Probability breakdown of one answer, highest first, with each option's tooltip description.
     *
     * @param Question $question
     * @param array{choice:string,confidence:float,probabilities:array<string,float>} $answer
     * @return array<int,array{label:string,description:string|null,percent:int}>
     */
    private function getProbabilityRows(Question $question, array $answer): array
    {
        $probabilities = $answer['probabilities'];
        arsort($probabilities);
        $rows = [];
        foreach ($probabilities as $code => $probability) {
            $percent = (int)round($probability * 100);
            if ($percent <= 0) {
                continue;
            }
            $rows[] = [
                'label' => (string)__($question->getOptionLabel((string)$code)),
                'description' => $this->translateOrNull($question->getOptionDescription((string)$code)),
                'percent' => $percent,
            ];
        }
        return $rows;
    }

    /**
     * Translate a source string, or return null when there is nothing to translate.
     *
     * @param string|null $text
     * @return string|null
     */
    private function translateOrNull(?string $text): ?string
    {
        return $text === null ? null : (string)__($text);
    }

    /**
     * URL of the "Refresh Jev" action for the current entity.
     *
     * @return string
     */
    public function getRefreshUrl(): string
    {
        return $this->getUrl('jev/refresh/' . $this->getEntityType(), ['id' => $this->getEntityId()]);
    }

    /**
     * URL of the Jev configuration section.
     *
     * @return string
     */
    public function getConfigUrl(): string
    {
        return $this->getUrl('adminhtml/system_config/edit', ['section' => 'jev']);
    }

    /**
     * Last successful analysis in the admin locale, or an empty string.
     *
     * @return string
     */
    public function getAnalyzedAt(): string
    {
        $analysis = $this->getAnalysis();
        if ($analysis === null || $analysis->getAnalyzedAt() === null) {
            return '';
        }
        return $this->formatDate($analysis->getAnalyzedAt(), \IntlDateFormatter::MEDIUM, true);
    }

    /**
     * Error message of the last failed analysis, or an empty string.
     *
     * @return string
     */
    public function getError(): string
    {
        $analysis = $this->getAnalysis();
        return $analysis !== null ? (string)$analysis->getError() : '';
    }

    /**
     * Name of the model that produced the last analysis, or an empty string.
     *
     * @return string
     */
    public function getModelName(): string
    {
        $analysis = $this->getAnalysis();
        return $analysis !== null ? (string)$analysis->getData(Analysis::MODEL) : '';
    }
}
