<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Client;

use HenriqueKieckbusch\Jev\Exception\ApiException;
use HenriqueKieckbusch\Jev\Exception\AuthenticationException;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Turns a failed Typesafe API response (status + body) into the right ApiException.
 */
class ErrorMapper
{
    /**
     * @param Json $json
     */
    public function __construct(
        private readonly Json $json
    ) {
    }

    /**
     * Map a failed HTTP status and body to the right exception type.
     *
     * @param int $status
     * @param string $body
     * @return ApiException
     */
    public function toException(int $status, string $body): ApiException
    {
        $detail = $this->extractMessage($body);
        if ($status === 401 || $status === 403) {
            return new AuthenticationException(__('The Jev API key was rejected (HTTP %1). %2', $status, $detail));
        }
        if ($status === 0) {
            return new ApiException(__('The Jev API could not be reached.'));
        }
        return new ApiException(__('The Jev API answered with HTTP %1. %2', $status, $detail));
    }

    /**
     * Human readable message from an error body such as {"detail": {"message": "..."}} or {"detail": [...]}.
     *
     * @param string $body
     * @return string
     */
    private function extractMessage(string $body): string
    {
        try {
            $data = $body === '' ? [] : $this->json->unserialize($body);
        } catch (\InvalidArgumentException $e) {
            return mb_substr($body, 0, 200);
        }
        $detail = is_array($data) ? ($data['detail'] ?? $data) : $data;
        if (is_array($detail)) {
            $detail = $detail['message'] ?? $detail['msg'] ?? $this->json->serialize($detail);
        }
        return mb_substr((string)$detail, 0, 200);
    }
}
