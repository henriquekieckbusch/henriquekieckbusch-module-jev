<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model;

use HenriqueKieckbusch\Jev\Exception\ApiException;
use HenriqueKieckbusch\Jev\Exception\DisabledException;
use HenriqueKieckbusch\Jev\Model\Analyzer\Result;
use HenriqueKieckbusch\Jev\Model\Analyzer\ResultFactory;
use HenriqueKieckbusch\Jev\Model\Handler\Pool;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Runs one Jev analysis: builds the entity context, asks every question of the
 * entity type in a single API call, stores the answers on the entity and keeps
 * the raw answers (with confidence) in the analysis table.
 */
class Analyzer
{
    /**
     * @param Config $config
     * @param Pool $pool
     * @param Client $client
     * @param AnalysisRepository $analysisRepository
     * @param ResultFactory $resultFactory
     * @param Json $json
     * @param DateTime $dateTime
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Config $config,
        private readonly Pool $pool,
        private readonly Client $client,
        private readonly AnalysisRepository $analysisRepository,
        private readonly ResultFactory $resultFactory,
        private readonly Json $json,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Analyze one entity. Without $force the API is not called again when the context is unchanged.
     *
     * @param string $type Entity type code, e.g. "order"
     * @param int $entityId
     * @param bool $force
     * @return Result
     * @throws DisabledException When Jev is disabled or has no API key
     * @throws NoSuchEntityException When the entity does not exist
     * @throws ApiException When the API call fails (the error is also stored in the analysis row)
     * @throws LocalizedException
     */
    public function analyze(string $type, int $entityId, bool $force = false): Result
    {
        if (!$this->config->isReady()) {
            throw new DisabledException(
                __('Jev is disabled or has no API key. Check Stores > Configuration > Services > Jev.')
            );
        }
        $handler = $this->pool->get($type);
        $context = $handler->buildContext($entityId);
        $hash = hash('sha256', $this->json->serialize($context));

        $analysis = $this->analysisRepository->getOrCreate($type, $entityId);
        $unchanged = $analysis->hasAnswers() && $analysis->getError() === null
            && $analysis->getContextHash() === $hash;
        if (!$force && $unchanged) {
            return $this->resultFactory->create(['analysis' => $analysis, 'skipped' => true]);
        }

        try {
            $response = $this->client->ask($context, $handler->getQuestions()->getQuestions());
        } catch (ApiException $e) {
            $analysis->recordError($e->getMessage());
            $this->analysisRepository->save($analysis);
            throw $e;
        }

        $handler->persist($entityId, $response->getChoices());
        $analysis->recordSuccess($response, $hash, $this->dateTime->gmtDate());
        $this->analysisRepository->save($analysis);

        return $this->resultFactory->create(['analysis' => $analysis, 'skipped' => false]);
    }

    /**
     * Analyze quietly: never throws, returns null on any failure. Meant for save observers and cron.
     *
     * @param string $type
     * @param int $entityId
     * @param bool $force
     * @return Result|null
     */
    public function tryAnalyze(string $type, int $entityId, bool $force = false): ?Result
    {
        try {
            return $this->analyze($type, $entityId, $force);
        } catch (DisabledException $e) {
            return null;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Jev analysis of %s #%d failed: %s', $type, $entityId, $e->getMessage()));
            return null;
        }
    }
}
