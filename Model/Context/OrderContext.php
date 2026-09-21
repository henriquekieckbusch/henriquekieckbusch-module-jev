<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Context;

use HenriqueKieckbusch\Jev\Model\ResourceModel\CustomerHistory;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Reads the payment, address, items and customer-history fields of an order,
 * used as Jev context alongside the order's own core fields.
 */
class OrderContext
{
    /**
     * @param CustomerHistory $customerHistory
     */
    public function __construct(
        private readonly CustomerHistory $customerHistory
    ) {
    }

    /**
     * Collect the order's payment fields, or an empty array if there is no payment.
     *
     * @param OrderInterface $order
     * @return array<string,mixed>
     */
    public function getPaymentData(OrderInterface $order): array
    {
        $payment = $order->getPayment();
        if ($payment === null) {
            return [];
        }
        return [
            'method' => $payment->getMethod(),
            'cc_type' => $payment->getCcType(),
            'cc_last4' => $payment->getCcLast4(),
            'cc_avs_status' => $payment->getCcAvsStatus(),
            'cc_cid_status' => $payment->getCcCidStatus(),
            'amount_paid' => (float)$payment->getAmountPaid(),
            'amount_authorized' => (float)$payment->getAmountAuthorized(),
        ];
    }

    /**
     * Collect the address fields, or an empty array if the address is missing.
     *
     * @param OrderAddressInterface|null $address
     * @return array<string,mixed>
     */
    public function getAddressData(?OrderAddressInterface $address): array
    {
        if ($address === null) {
            return [];
        }
        return [
            'firstname' => $address->getFirstname(),
            'lastname' => $address->getLastname(),
            'company' => $address->getCompany(),
            'street' => implode(', ', (array)$address->getStreet()),
            'city' => $address->getCity(),
            'region' => $address->getRegionCode(),
            'postcode' => $address->getPostcode(),
            'country_id' => $address->getCountryId(),
            'telephone' => $address->getTelephone(),
        ];
    }

    /**
     * Whether the billing and shipping addresses share the same country and postcode.
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function addressesMatch(OrderInterface $order): bool
    {
        $billing = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();
        if ($billing === null || $shipping === null) {
            return true;
        }
        return $billing->getCountryId() === $shipping->getCountryId()
            && mb_strtolower((string)$billing->getPostcode()) === mb_strtolower((string)$shipping->getPostcode());
    }

    /**
     * List the order's top-level (non-child) items with their core fields.
     *
     * @param OrderInterface $order
     * @return array<int,array<string,mixed>>
     */
    public function getItemsData(OrderInterface $order): array
    {
        $items = [];
        foreach ($order->getItems() as $item) {
            if ((float)$item->getParentItemId()) {
                continue;
            }
            $items[] = [
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'product_type' => $item->getProductType(),
                'qty_ordered' => (float)$item->getQtyOrdered(),
                'price' => (float)$item->getPrice(),
                'row_total' => (float)$item->getRowTotal(),
            ];
        }
        return $items;
    }

    /**
     * Look up the placing customer's order history, keyed by customer id or guest email.
     *
     * @param OrderInterface $order
     * @return array<string,mixed>
     */
    public function getCustomerHistory(OrderInterface $order): array
    {
        $history = $order->getCustomerId()
            ? $this->customerHistory->byCustomerId((int)$order->getCustomerId(), (int)$order->getEntityId())
            : $this->customerHistory->byEmail((string)$order->getCustomerEmail(), (int)$order->getEntityId());
        $history['is_first_order'] = $history['orders_count'] === 0;
        return $history;
    }
}
