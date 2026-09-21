<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model;

use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Writes Jev answers to customer EAV attributes without a full customer save,
 * and refreshes the customer grid row when the indexer is not schedule-based.
 */
class CustomerAttributeWriter
{
    /**
     * @param CustomerFactory $customerFactory
     * @param CustomerResource $customerResource
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        private readonly CustomerFactory $customerFactory,
        private readonly CustomerResource $customerResource,
        private readonly IndexerRegistry $indexerRegistry
    ) {
    }

    /**
     * Write the given Jev answers as customer EAV attribute values.
     *
     * @param int $customerId
     * @param array<string,string> $attributeValues Attribute code (with "jev_" prefix) => value
     * @return void
     */
    public function write(int $customerId, array $attributeValues): void
    {
        if ($attributeValues === []) {
            return;
        }
        /** @var CustomerModel $customer */
        $customer = $this->customerFactory->create();
        $customer->setId($customerId);
        foreach ($attributeValues as $code => $value) {
            $customer->setData($code, $value);
            $this->customerResource->saveAttribute($customer, $code);
        }
        $indexer = $this->indexerRegistry->get(CustomerModel::CUSTOMER_GRID_INDEXER_ID);
        if (!$indexer->isScheduled()) {
            $indexer->reindexRow($customerId);
        }
    }
}
