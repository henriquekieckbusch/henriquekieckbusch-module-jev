<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Handler;

use HenriqueKieckbusch\Jev\Model\Context\Age;
use HenriqueKieckbusch\Jev\Model\Context\CustomerEngagement;
use HenriqueKieckbusch\Jev\Model\CustomerAttributeWriter;
use HenriqueKieckbusch\Jev\Model\Question\CustomerQuestions;
use HenriqueKieckbusch\Jev\Model\Question\QuestionsInterface;
use HenriqueKieckbusch\Jev\Model\ResourceModel\CustomerHistory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Builds the value/quality context of a customer and stores Jev's answers as
 * customer EAV attributes, without triggering a full customer save.
 */
class Customer implements HandlerInterface
{
    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerEngagement $engagement
     * @param CustomerAttributeWriter $attributeWriter
     * @param CustomerHistory $customerHistory
     * @param Age $age
     * @param CustomerQuestions $questions
     */
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly CustomerEngagement $engagement,
        private readonly CustomerAttributeWriter $attributeWriter,
        private readonly CustomerHistory $customerHistory,
        private readonly Age $age,
        private readonly CustomerQuestions $questions
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return 'customer';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return 'Customer';
    }

    /**
     * @inheritdoc
     */
    public function getQuestions(): QuestionsInterface
    {
        return $this->questions;
    }

    /**
     * @inheritdoc
     */
    public function buildContext(int $entityId): array
    {
        $customer = $this->customerRepository->getById($entityId);
        return [
            'customer' => $this->getCustomerData($customer),
            'orders' => $this->customerHistory->byCustomerId($entityId),
            'reviews_count' => $this->engagement->getReviewsCount($entityId),
            'wishlist_items_count' => $this->engagement->getWishlistItemsCount($entityId),
            'newsletter_subscribed' => $this->engagement->isNewsletterSubscribed($customer),
            'addresses_count' => count($customer->getAddresses() ?? []),
        ];
    }

    /**
     * Collect the core customer fields used as Jev context.
     *
     * @param CustomerInterface $customer
     * @return array<string,mixed>
     */
    private function getCustomerData(CustomerInterface $customer): array
    {
        return [
            'account_age_days' => $this->age->daysSince($customer->getCreatedAt()),
            'group' => $this->engagement->getGroupCode((int)$customer->getGroupId()),
            'days_since_last_login' => $this->age->daysSince(
                $this->engagement->getLastLoginAt((int)$customer->getId())
            ),
            'email_domain' => substr((string)strrchr((string)$customer->getEmail(), '@'), 1),
        ];
    }

    /**
     * @inheritdoc
     */
    public function persist(int $entityId, array $choices): void
    {
        $attributeValues = [];
        foreach ($choices as $code => $choice) {
            $attributeValues['jev_' . $code] = $choice;
        }
        $this->attributeWriter->write($entityId, $attributeValues);
    }
}
