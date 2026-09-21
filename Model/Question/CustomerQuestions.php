<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Question;

/**
 * Questions asked about a customer account (quality, value and retention).
 */
class CustomerQuestions extends AbstractQuestions
{
    /**
     * @inheritdoc
     */
    protected function getDefinitions(): array
    {
        return $this->getValueDefinitions() + $this->getBehaviorDefinitions() + $this->getSegmentDefinitions();
    }

    /**
     * Quality and value questions.
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, string|null>}>
     */
    private function getValueDefinitions(): array
    {
        return [
            'customer_quality' => [
                'Customer Quality',
                'What is the overall quality of this customer for the store? Consider spending, order frequency, '
                . 'cancellations, refunds, coupon dependency and engagement.',
                [
                    'excellent' => 'Frequent, high spending, no problems',
                    'good' => 'Repeat buyer with few problems',
                    'normal' => 'Average or new customer without notable signals',
                    'low' => 'Rare purchases, low value or some problems',
                    'problematic' => 'Many cancellations, refunds or abuse signals',
                ],
            ],
            'ltv_potential' => [
                'LTV Potential',
                'What is the lifetime value potential of this customer? Consider spending so far, order frequency, '
                . 'recency, account age and engagement such as wishlist, reviews and newsletter.',
                [
                    'very_low' => null,
                    'low' => null,
                    'medium' => null,
                    'high' => null,
                    'very_high' => null,
                ],
            ],
            'churn_risk' => [
                'Churn Risk',
                'What is the risk that this customer stops buying? Consider days since the last order compared to '
                . 'the usual gap between orders, declining frequency and negative experiences such as refunds.',
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
     * Behavioral risk questions: returns, fraud and coupon dependency/abuse.
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, string|null>}>
     */
    private function getBehaviorDefinitions(): array
    {
        return [
            'return_propensity' => [
                'Return Propensity',
                'How likely is this customer to return products or ask for refunds? Base it on the share of '
                . 'canceled, closed and refunded orders and credit memos in the customer history.',
                [
                    'very_low' => null,
                    'low' => null,
                    'medium' => null,
                    'high' => null,
                    'very_high' => null,
                ],
            ],
            'fraud_risk' => [
                'Fraud Risk',
                'What is the fraud risk associated with this customer? Consider suspicious email domain, very new '
                . 'account with high spending, many canceled orders, many addresses or inconsistent data.',
                [
                    'very_low' => null,
                    'low' => null,
                    'medium' => null,
                    'high' => null,
                    'critical' => 'Clear fraud pattern',
                ],
            ],
            'discount_sensitivity' => [
                'Discount Sensitivity',
                'How much does this customer depend on discounts to buy? Use the share of orders that used a '
                . 'coupon and the discount amount relative to the total spent.',
                [
                    'not_sensitive' => 'Buys at full price',
                    'slightly_sensitive' => 'Occasionally uses a coupon',
                    'moderately_sensitive' => 'Uses coupons in a large share of orders',
                    'highly_sensitive' => 'Almost never buys without a coupon',
                ],
            ],
            'coupon_abuse' => [
                'Coupon Abuse',
                'Is there evidence of coupon abuse by this customer, such as many distinct coupon codes, discounts '
                . 'that are unusually large compared to spending or orders canceled after using coupons?',
                [
                    'none' => 'No evidence',
                    'suspicious' => 'Some unusual coupon usage',
                    'likely_abuse' => 'Pattern strongly suggests abuse',
                    'confirmed_abuse' => 'Data leaves no doubt',
                ],
            ],
        ];
    }

    /**
     * Segmentation and service-strategy questions.
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, string|null>}>
     */
    private function getSegmentDefinitions(): array
    {
        return [
            'customer_segment' => [
                'Customer Segment',
                'Which segment best represents this customer?',
                [
                    'new' => 'Recently created account with zero or one order',
                    'occasional' => 'Buys rarely',
                    'regular' => 'Buys with some regularity',
                    'high_value' => 'Spends far above the average',
                    'vip' => 'Frequent and very high spending',
                    'at_risk' => 'Used to buy but has gone quiet',
                    'problematic' => 'Cancellations, refunds or abuse',
                ],
            ],
            'support_priority' => [
                'Support Priority',
                'What priority should this customer receive in customer support?',
                [
                    'normal' => null,
                    'priority' => null,
                    'high' => null,
                    'vip' => null,
                    'critical' => 'Immediate attention, very valuable or at serious risk',
                ],
            ],
            'retention_strategy' => [
                'Retention Strategy',
                'Which strategy should be used to increase this customer\'s retention?',
                [
                    'none' => 'No action needed right now',
                    'engagement' => 'Content, newsletter and reminders',
                    'discount' => 'A discount offer',
                    'personalized_offer' => 'An offer based on past purchases',
                    'loyalty_reward' => 'Reward loyalty with points, gifts or early access',
                    'sales_contact' => 'A personal contact from the sales team',
                ],
            ],
        ];
    }
}
