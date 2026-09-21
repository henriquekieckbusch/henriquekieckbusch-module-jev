<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Handler;

use HenriqueKieckbusch\Jev\Model\Context\Age;
use HenriqueKieckbusch\Jev\Model\Question\QuestionsInterface;
use HenriqueKieckbusch\Jev\Model\Question\QuoteQuestions;
use HenriqueKieckbusch\Jev\Model\ResourceModel\CustomerHistory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote as QuoteModel;

/**
 * Builds the recovery context of an abandoned cart and writes Jev's answers with
 * a direct SQL update, so quote totals collection and version control are never triggered.
 */
class Quote implements HandlerInterface
{
    private const TABLE = 'quote';

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param CustomerHistory $customerHistory
     * @param ResourceConnection $resourceConnection
     * @param Age $age
     * @param QuoteQuestions $questions
     */
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly CustomerHistory $customerHistory,
        private readonly ResourceConnection $resourceConnection,
        private readonly Age $age,
        private readonly QuoteQuestions $questions
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return 'quote';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return 'Abandoned Cart';
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
        try {
            /** @var QuoteModel $quote */
            $quote = $this->cartRepository->get($entityId);
        } catch (NoSuchEntityException $e) {
            throw $e;
        }
        return [
            'cart' => [
                'items_count' => (int)$quote->getItemsCount(),
                'items_qty' => (float)$quote->getItemsQty(),
                'subtotal' => (float)$quote->getSubtotal(),
                'grand_total' => (float)$quote->getGrandTotal(),
                'coupon_code' => $quote->getCouponCode(),
                'customer_is_guest' => (bool)$quote->getCustomerIsGuest(),
                'abandoned_hours_ago' => $this->age->hoursSince($quote->getUpdatedAt()),
                'reached_shipping_step' => (bool)($quote->getShippingAddress()
                    && $quote->getShippingAddress()->getShippingMethod()),
                'country' => $quote->getShippingAddress() ? $quote->getShippingAddress()->getCountryId() : null,
            ],
            'items' => $this->getItemsData($quote),
            'customer_history' => $quote->getCustomerId()
                ? $this->customerHistory->byCustomerId((int)$quote->getCustomerId())
                : $this->customerHistory->byEmail((string)$quote->getCustomerEmail()),
        ];
    }

    /**
     * List the cart's visible items with their core fields.
     *
     * @param QuoteModel $quote
     * @return array<int, array<string, mixed>>
     */
    private function getItemsData(QuoteModel $quote): array
    {
        $items = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $items[] = [
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'qty' => (float)$item->getQty(),
                'price' => (float)$item->getPrice(),
                'row_total' => (float)$item->getRowTotal(),
            ];
        }
        return $items;
    }

    /**
     * @inheritdoc
     */
    public function persist(int $entityId, array $choices): void
    {
        if ($choices === []) {
            return;
        }
        $data = [];
        foreach ($choices as $code => $choice) {
            $data['jev_' . $code] = $choice;
        }
        $connection = $this->resourceConnection->getConnection('checkout');
        $connection->update(
            $this->resourceConnection->getTableName(self::TABLE),
            $data,
            ['entity_id = ?' => $entityId]
        );
    }
}
