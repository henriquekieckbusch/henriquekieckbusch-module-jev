<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model;

use HenriqueKieckbusch\Jev\Exception\ApiException;
use HenriqueKieckbusch\Jev\Exception\AuthenticationException;
use HenriqueKieckbusch\Jev\Model\Client\Response;
use HenriqueKieckbusch\Jev\Model\Client\ResponseFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Minimal HTTP client for the Typesafe "System One" API (https://docs.typesafe.ai/api).
 *
 * One call sends the whole entity context as `state` and every question of the entity
 * as a `choice` question; the model answers all of them in a single round trip.
 */
class Client
{
    private const ENDPOINT_ASK = '/v1/systemone';
    private const ENDPOINT_MODELS = '/v1/models';
    private const RETRY_STATUSES = [408, 429, 500, 502, 503, 504, 529];

    /**
     * @param Config $config
     * @param CurlFactory $curlFactory
     * @param Json $json
     * @param ResponseFactory $responseFactory
     * @param Client\ErrorMapper $errorMapper
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Config $config,
        private readonly CurlFactory $curlFactory,
        private readonly Json $json,
        private readonly ResponseFactory $responseFactory,
        private readonly Client\ErrorMapper $errorMapper,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Ask all questions about one state (entity context) in a single API call.
     *
     * @param array<string,mixed> $state
     * @param Question[] $questions
     * @return Response
     * @throws ApiException
     */
    public function ask(array $state, array $questions): Response
    {
        $payload = [
            'state' => $state,
            'model' => $this->config->getModel(),
            'questions' => [],
        ];
        foreach ($questions as $question) {
            $payload['questions'][$question->getCode()] = $question->toApiPayload();
        }
        $body = $this->request(
            'POST',
            self::ENDPOINT_ASK,
            $this->config->getApiKey(),
            $this->json->serialize($payload)
        );
        return $this->parse($body, $questions);
    }

    /**
     * Check an API key against the API. Returns false only when the key is rejected.
     *
     * @param string $apiKey
     * @return bool
     * @throws ApiException When the API cannot be reached
     */
    public function isApiKeyValid(string $apiKey): bool
    {
        try {
            $this->request('GET', self::ENDPOINT_MODELS, $apiKey);
        } catch (AuthenticationException $e) {
            return false;
        }
        return true;
    }

    /**
     * Perform one HTTP request with a single retry on transient failures.
     *
     * @param string $method
     * @param string $path
     * @param string $apiKey
     * @param string|null $body JSON body for POST requests
     * @return string Response body
     * @throws ApiException
     */
    private function request(string $method, string $path, string $apiKey, ?string $body = null): string
    {
        $url = Config::API_BASE_URL . $path;
        $attempt = 0;
        while (true) {
            $attempt++;
            $curl = $this->createCurl($apiKey);
            try {
                if ($method === 'POST') {
                    $curl->post($url, (string)$body);
                } else {
                    $curl->get($url);
                }
                $status = (int)$curl->getStatus();
            } catch (\Exception $e) {
                $status = 0;
                $this->logger->warning('Jev API connection error: ' . $e->getMessage());
            }
            $responseBody = (string)$curl->getBody();
            $this->debug($method, $path, $body, $status, $responseBody);
            if ($status >= 200 && $status < 300) {
                return $responseBody;
            }
            if ($attempt >= 2 || ($status !== 0 && !in_array($status, self::RETRY_STATUSES, true))) {
                throw $this->errorMapper->toException($status, $responseBody);
            }
        }
    }

    /**
     * Build a cURL client preconfigured with the Jev API headers and timeout.
     *
     * @param string $apiKey
     * @return Curl
     */
    private function createCurl(string $apiKey): Curl
    {
        $curl = $this->curlFactory->create();
        $curl->setTimeout($this->config->getTimeout());
        $curl->addHeader('Authorization', 'Bearer ' . $apiKey);
        $curl->addHeader('Content-Type', 'application/json');
        $curl->addHeader('Accept', 'application/json');
        return $curl;
    }

    /**
     * Turn the raw body into a Response, keeping only answers whose choice is a known option.
     *
     * @param string $body
     * @param Question[] $questions
     * @return Response
     * @throws ApiException
     */
    private function parse(string $body, array $questions): Response
    {
        try {
            $data = $this->json->unserialize($body);
        } catch (\InvalidArgumentException $e) {
            throw new ApiException(__('The Jev API returned an invalid JSON body.'), $e);
        }
        if (!is_array($data) || !isset($data['answers']) || !is_array($data['answers'])) {
            throw new ApiException(__('The Jev API response has no answers.'));
        }
        $answers = [];
        foreach ($questions as $question) {
            $answer = $this->parseAnswer($question, $data['answers'][$question->getCode()] ?? null);
            if ($answer !== null) {
                $answers[$question->getCode()] = $answer;
            }
        }
        return $this->responseFactory->create([
            'model' => (string)($data['model'] ?? ''),
            'answers' => $answers,
            'inputTokens' => (int)($data['usage']['input_tokens'] ?? 0),
            'outputTokens' => (int)($data['usage']['output_tokens'] ?? 0),
        ]);
    }

    /**
     * Validate and normalize one raw answer, or return null when it is missing or unusable.
     *
     * @param Question $question
     * @param mixed $rawAnswer
     * @return array{choice:string,confidence:float,probabilities:array<string,float>}|null
     */
    private function parseAnswer(Question $question, mixed $rawAnswer): ?array
    {
        $choice = is_array($rawAnswer) ? (string)($rawAnswer['choice'] ?? '') : '';
        if ($choice === '' || !$question->hasOption($choice)) {
            return null;
        }
        $probabilities = is_array($rawAnswer['probabilities'] ?? null) ? $rawAnswer['probabilities'] : [];
        return [
            'choice' => $choice,
            'confidence' => round((float)($rawAnswer['confidence'] ?? 0), 4),
            'probabilities' => array_map(static fn ($value): float => round((float)$value, 4), $probabilities),
        ];
    }

    /**
     * Write the exchange to the Jev log when debug logging is on.
     *
     * @param string $method
     * @param string $path
     * @param string|null $body
     * @param int $status
     * @param string $responseBody
     * @return void
     */
    private function debug(string $method, string $path, ?string $body, int $status, string $responseBody): void
    {
        if (!$this->config->isDebug()) {
            return;
        }
        $this->logger->debug(
            sprintf('Jev %s %s -> HTTP %d', $method, $path, $status),
            ['request' => $body, 'response' => $responseBody]
        );
    }
}
