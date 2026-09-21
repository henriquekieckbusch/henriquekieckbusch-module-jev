<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\ResourceModel\Grid as OrderGridResource;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

/**
 * Writes Jev answers to order attributes without a full order save, refreshes the
 * orders grid row, and adds an internal comment when the recommended action is noteworthy.
 */
class OrderAttributeWriter
{
    private const RECOMMENDED_ACTION_NOTEWORTHY = ['manual_review', 'hold', 'reject'];

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderResource $orderResource
     * @param OrderGridResource $orderGrid
     * @param OrderStatusHistoryRepositoryInterface $historyRepository
     * @param Config $config
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderResource $orderResource,
        private readonly OrderGridResource $orderGrid,
        private readonly OrderStatusHistoryRepositoryInterface $historyRepository,
        private readonly Config $config
    ) {
    }

    /**
     * Write the given Jev answers as order attribute values.
     *
     * @param int $orderId
     * @param array<string,string> $attributeValues Attribute code (with "jev_" prefix) => value
     * @return void
     */
    public function write(int $orderId, array $attributeValues): void
    {
        if ($attributeValues === []) {
            return;
        }
        /** @var OrderModel $order */
        $order = $this->orderRepository->get($orderId);
        foreach ($attributeValues as $code => $value) {
            $order->setData($code, $value);
        }
        $this->orderResource->saveAttribute($order, array_keys($attributeValues));
        $this->orderGrid->refresh($orderId);
        $this->maybeAddComment($order, $attributeValues);
    }

    /**
     * Add an internal status history comment when the recommended action is noteworthy.
     *
     * @param OrderModel $order
     * @param array<string,string> $attributeValues
     * @return void
     */
    private function maybeAddComment(OrderModel $order, array $attributeValues): void
    {
        $action = $attributeValues['jev_recommended_action'] ?? null;
        if (!$this->config->isOrderCommentEnabled() || $action === null
            || !in_array($action, self::RECOMMENDED_ACTION_NOTEWORTHY, true)
        ) {
            return;
        }
        $comment = __(
            'Jev recommends "%1" (risk: %2, fraud: %3).',
            $order->getData('jev_recommended_action'),
            $order->getData('jev_order_risk') ?: '-',
            $order->getData('jev_fraud_risk') ?: '-'
        );
        $history = $order->addCommentToStatusHistory((string)$comment, false, false);
        $this->historyRepository->save($history);
    }
}
