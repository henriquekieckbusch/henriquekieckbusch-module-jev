<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Context;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Logger as CustomerLogger;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory as WishlistItemCollectionFactory;

/**
 * Reads customer engagement signals (group, last login, reviews, wishlist,
 * newsletter) that live outside the customer entity itself.
 */
class CustomerEngagement
{
    /**
     * @param GroupRepositoryInterface $groupRepository
     * @param CustomerLogger $customerLogger
     * @param SubscriberFactory $subscriberFactory
     * @param ReviewCollectionFactory $reviewCollectionFactory
     * @param WishlistItemCollectionFactory $wishlistItemCollectionFactory
     */
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly CustomerLogger $customerLogger,
        private readonly SubscriberFactory $subscriberFactory,
        private readonly ReviewCollectionFactory $reviewCollectionFactory,
        private readonly WishlistItemCollectionFactory $wishlistItemCollectionFactory
    ) {
    }

    /**
     * Days since the customer last logged in, or null if never.
     *
     * @param int $customerId
     * @return string|null
     */
    public function getLastLoginAt(int $customerId): ?string
    {
        return $this->customerLogger->get($customerId)->getLastLoginAt();
    }

    /**
     * Resolve the customer group code, or an empty string if it no longer exists.
     *
     * @param int $groupId
     * @return string
     */
    public function getGroupCode(int $groupId): string
    {
        try {
            return (string)$this->groupRepository->getById($groupId)->getCode();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Count the reviews written by the customer.
     *
     * @param int $customerId
     * @return int
     */
    public function getReviewsCount(int $customerId): int
    {
        return $this->reviewCollectionFactory->create()->addCustomerFilter($customerId)->getSize();
    }

    /**
     * Count the items in the customer's wishlist.
     *
     * @param int $customerId
     * @return int
     */
    public function getWishlistItemsCount(int $customerId): int
    {
        return $this->wishlistItemCollectionFactory->create()->addCustomerIdFilter($customerId)->getSize();
    }

    /**
     * Whether the customer is subscribed to the newsletter.
     *
     * @param CustomerInterface $customer
     * @return bool
     */
    public function isNewsletterSubscribed(CustomerInterface $customer): bool
    {
        $subscriber = $this->subscriberFactory->create()
            ->loadByCustomer((int)$customer->getId(), (int)$customer->getWebsiteId());
        return (bool)$subscriber->isSubscribed();
    }
}
