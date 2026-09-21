<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Config\Backend;

use HenriqueKieckbusch\Jev\Exception\ApiException;
use HenriqueKieckbusch\Jev\Model\Client;
use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Encrypted API key field that checks a new key against the Typesafe API before it is saved.
 */
class ApiKey extends Encrypted
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param EncryptorInterface $encryptor
     * @param Client $client
     * @param ManagerInterface $messageManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        EncryptorInterface $encryptor,
        private readonly Client $client,
        private readonly ManagerInterface $messageManager,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $encryptor,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Reject a key the API does not accept; then encrypt as usual.
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = trim((string)$this->getValue());
        if ($value !== '' && !preg_match('/^\*+$/', $value)) {
            $this->validate($value);
        }
        return parent::beforeSave();
    }

    /**
     * Verify the API key against the Typesafe API, warning (not failing) if it cannot be checked.
     *
     * @param string $apiKey
     * @return void
     * @throws LocalizedException
     */
    private function validate(string $apiKey): void
    {
        try {
            if (!$this->client->isApiKeyValid($apiKey)) {
                throw new LocalizedException(
                    __('Typesafe rejected this Jev API key. Copy the token again from console.typesafe.ai.')
                );
            }
        } catch (ApiException $e) {
            $this->messageManager->addWarningMessage(
                __('The Jev API key was saved but could not be verified: %1', $e->getMessage())
            );
        }
    }
}
