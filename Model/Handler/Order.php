<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Handler;

use HenriqueKieckbusch\Jev\Model\Context\Age;
use HenriqueKieckbusch\Jev\Model\Context\OrderContext;
use HenriqueKieckbusch\Jev\Model\OrderAttributeWriter;
use HenriqueKieckbusch\Jev\Model\Question\OrderQuestions;
use HenriqueKieckbusch\Jev\Model\Question\QuestionsInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Builds the risk context of an order and stores Jev's answers on the order
 * (and, when they recommend action, adds an internal status history comment).
 */
class Order implements HandlerInterface
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderContext $context
     * @param OrderAttributeWriter $attributeWriter
     * @param Age $age
     * @param OrderQuestions $questions
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderContext $context,
        private readonly OrderAttributeWriter $attributeWriter,
        private readonly Age $age,
        private readonly OrderQuestions $questions
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return 'order';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return 'Order';
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
        $order = $this->orderRepository->get($entityId);
        return [
            'order' => $this->getOrderData($order),
            'payment' => $this->context->getPaymentData($order),
            'billing_address' => $this->context->getAddressData($order->getBillingAddress()),
            'shipping_address' => $this->context->getAddressData($order->getShippingAddress()),
            'addresses_match' => $this->context->addressesMatch($order),
            'items' => $this->context->getItemsData($order),
            'customer_history' => $this->context->getCustomerHistory($order),
        ];
    }

    /**
     * Collect the core order fields used as Jev context.
     *
     * @param OrderInterface $order
     * @return array<string,mixed>
     */
    private function getOrderData(OrderInterface $order): array
    {
        return [
            'increment_id' => $order->getIncrementId(),
            'status' => $order->getStatus(),
            'state' => $order->getState(),
            'created_hours_ago' => $this->age->hoursSince($order->getCreatedAt()),
            'currency' => $order->getOrderCurrencyCode(),
            'grand_total' => (float)$order->getGrandTotal(),
            'subtotal' => (float)$order->getSubtotal(),
            'shipping_amount' => (float)$order->getShippingAmount(),
            'discount_amount' => abs((float)$order->getDiscountAmount()),
            'coupon_code' => $order->getCouponCode(),
            'shipping_description' => $order->getShippingDescription(),
            'customer_is_guest' => (bool)$order->getCustomerIsGuest(),
            'customer_note' => $order->getCustomerNote(),
            'item_count' => (int)$order->getTotalItemCount(),
            'total_qty_ordered' => (float)$order->getTotalQtyOrdered(),
            'remote_ip' => $order->getRemoteIp(),
            'x_forwarded_for' => $order->getXForwardedFor(),
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
