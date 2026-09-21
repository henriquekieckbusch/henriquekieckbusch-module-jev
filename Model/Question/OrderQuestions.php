<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Question;

/**
 * Questions asked about a sales order (risk and fraud assessment).
 */
class OrderQuestions extends AbstractQuestions
{
    /**
     * @inheritdoc
     */
    protected function getDefinitions(): array
    {
        return $this->getRiskDefinitions() + $this->getDecisionDefinitions();
    }

    /**
     * The five risk dimensions Jev scores independently for the order.
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, string|null>}>
     */
    private function getRiskDefinitions(): array
    {
        return [
            'order_risk' => [
                'Order Risk',
                'What is the overall risk level of this order? Combine payment, fraud, fulfillment, '
                . 'shipping and customer history signals present in the order data.',
                [
                    'low' => 'Nothing unusual, safe to process normally',
                    'medium' => 'Some minor signals worth a second look',
                    'high' => 'Several risk signals, likely to cause a problem',
                    'critical' => 'Strong signals of fraud or certain loss',
                ],
            ],
            'fraud_risk' => [
                'Fraud Risk',
                'What is the fraud risk of this order? Consider mismatching addresses, guest checkout with a '
                . 'suspicious email domain, unusual order value, order velocity, IP data and payment details.',
                [
                    'very_low' => 'Established customer, consistent data',
                    'low' => 'Consistent data, no red flags',
                    'medium' => 'One or two weak red flags',
                    'high' => 'Multiple red flags typical of fraud',
                    'critical' => 'Clear fraud pattern',
                ],
            ],
            'payment_risk' => [
                'Payment Risk',
                'What is the risk of a problem with the payment of this order (chargeback, failed capture, '
                . 'never paid)? Consider the payment method, its verification results and the amounts paid.',
                [
                    'very_low' => null,
                    'low' => null,
                    'medium' => null,
                    'high' => null,
                    'very_high' => null,
                ],
            ],
            'return_risk' => [
                'Return Risk',
                'What is the risk that this order will be returned or refunded? Consider the product types, '
                . 'quantities, order value and the customer\'s history of cancellations and refunds.',
                [
                    'very_low' => null,
                    'low' => null,
                    'medium' => null,
                    'high' => null,
                    'very_high' => null,
                ],
            ],
            'fulfillment_risk' => [
                'Fulfillment Risk',
                'What is the risk that this order has problems during fulfillment (out of stock items, many '
                . 'line items, unusual quantities, incomplete data needed to ship)?',
                [
                    'very_low' => null,
                    'low' => null,
                    'medium' => null,
                    'high' => null,
                    'very_high' => null,
                ],
            ],
            'shipping_risk' => [
                'Shipping Risk',
                'What is the risk of a delay or delivery problem for this order? Consider the shipping method, '
                . 'the destination address quality and completeness, and whether a phone number is present.',
                [
                    'very_low' => null,
                    'low' => null,
                    'medium' => null,
                    'high' => null,
                    'very_high' => null,
                ],
            ],
        ];
    }

    /**
     * The operational decision questions derived from the risk scores.
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, string|null>}>
     */
    private function getDecisionDefinitions(): array
    {
        return [
            'manual_review' => [
                'Manual Review',
                'Should this order be manually reviewed by a person before it is fulfilled?',
                [
                    'no' => 'Safe to process automatically',
                    'yes' => 'A person should look at it before shipping',
                    'urgent' => 'Stop processing until a person reviews it',
                ],
            ],
            'main_risk_factor' => [
                'Main Risk Factor',
                'What is the main risk factor identified in this order? Answer "none" when the order looks normal.',
                [
                    'customer' => 'Customer identity or history',
                    'payment' => 'Payment method or payment verification results',
                    'address' => 'Billing or shipping address problems or mismatch',
                    'device' => 'Device or browser related signals',
                    'ip' => 'IP address related signals',
                    'velocity' => 'Too many orders in a short period',
                    'product' => 'Products typically targeted by fraud or with high return rate',
                    'shipping' => 'Shipping method or destination',
                    'order_value' => 'Order value far above the usual',
                    'multiple_factors' => 'Several factors combined',
                    'none' => 'No meaningful risk factor',
                ],
            ],
            'recommended_action' => [
                'Recommended Action',
                'What action should be taken for this order?',
                [
                    'approve' => 'Process normally',
                    'approve_with_monitoring' => 'Process but keep an eye on it',
                    'manual_review' => 'Have a person review it before processing',
                    'hold' => 'Put on hold until more information is available',
                    'reject' => 'Cancel the order',
                ],
            ],
        ];
    }
}
