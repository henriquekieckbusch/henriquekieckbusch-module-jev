<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Typed access to the Jev configuration (Stores > Configuration > Services > Jev).
 */
class Config
{
    public const XML_PATH_ENABLED = 'jev/general/enabled';
    public const XML_PATH_API_KEY = 'jev/general/api_key';
    public const XML_PATH_MODEL = 'jev/general/model';
    public const XML_PATH_TIMEOUT = 'jev/general/timeout';
    public const XML_PATH_DEBUG = 'jev/general/debug';
    public const XML_PATH_ORDER_COMMENT = 'jev/order/add_comment';
    public const XML_PATH_CART_ENABLED = 'jev/abandoned_cart/enabled';
    public const XML_PATH_CART_MIN_AGE = 'jev/abandoned_cart/min_age_hours';
    public const XML_PATH_CART_BATCH = 'jev/abandoned_cart/batch_size';

    public const API_BASE_URL = 'https://api.typesafe.ai';
    public const DEFAULT_MODEL = 'jev-latest';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Whether Jev is switched on.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED);
    }

    /**
     * Whether Jev is enabled and has an API key, i.e. calls can be made.
     *
     * @return bool
     */
    public function isReady(): bool
    {
        return $this->isEnabled() && $this->getApiKey() !== '';
    }

    /**
     * Decrypted Typesafe API key ("apikey_...").
     *
     * @return string
     */
    public function getApiKey(): string
    {
        // The value is already decrypted here: etc/config.xml declares this path with
        // backend_model="Encrypted", so ScopeConfigInterface::getValue() auto-decrypts it
        // (Magento\Config\Model\Config\TypePool metadata). Decrypting again would corrupt it.
        return trim((string)$this->scopeConfig->getValue(self::XML_PATH_API_KEY));
    }

    /**
     * Model name sent to the API, e.g. "jev-latest".
     *
     * @return string
     */
    public function getModel(): string
    {
        $value = trim((string)$this->scopeConfig->getValue(self::XML_PATH_MODEL));
        return $value === '' ? self::DEFAULT_MODEL : $value;
    }

    /**
     * HTTP timeout in seconds for one API call.
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return max(1, (int)$this->scopeConfig->getValue(self::XML_PATH_TIMEOUT) ?: 10);
    }

    /**
     * Whether request and response payloads are written to var/log/jev.log.
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_DEBUG);
    }

    /**
     * Whether a status history comment is added to orders that Jev flags.
     *
     * @return bool
     */
    public function isOrderCommentEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ORDER_COMMENT);
    }

    /**
     * Whether the abandoned cart cron job is enabled.
     *
     * @return bool
     */
    public function isAbandonedCartEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CART_ENABLED);
    }

    /**
     * Hours a cart must stay untouched before it is considered abandoned.
     *
     * @return int
     */
    public function getAbandonedCartMinAgeHours(): int
    {
        return max(1, (int)$this->scopeConfig->getValue(self::XML_PATH_CART_MIN_AGE) ?: 1);
    }

    /**
     * Maximum number of carts analyzed by one cron run.
     *
     * @return int
     */
    public function getAbandonedCartBatchSize(): int
    {
        return max(1, (int)$this->scopeConfig->getValue(self::XML_PATH_CART_BATCH) ?: 50);
    }
}
