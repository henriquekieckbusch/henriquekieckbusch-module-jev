<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Question;

/**
 * Questions asked about an abandoned cart (quote) to plan its recovery.
 */
class QuoteQuestions extends AbstractQuestions
{
    /**
     * @inheritdoc
     */
    protected function getDefinitions(): array
    {
        return [
            'recovery_priority' => [
                'Recovery Priority',
                'What is the priority of recovering this abandoned cart? Consider the cart value, how far the '
                . 'customer got in checkout, the time since the last update and the customer history.',
                [
                    'none' => 'Not worth pursuing',
                    'low' => null,
                    'medium' => null,
                    'high' => null,
                    'urgent' => 'High value and still fresh, act now',
                ],
            ],
            'recovery_strategy' => [
                'Recovery Strategy',
                'Which strategy should be used to recover this cart?',
                [
                    'none' => null,
                    'email' => 'A reminder email',
                    'push' => 'A push or SMS notification',
                    'coupon' => 'An email with a coupon',
                    'free_shipping' => 'An offer of free shipping',
                    'sales_contact' => 'A personal contact from sales',
                    'retargeting' => 'Retargeting ads',
                ],
            ],
            'recovery_incentive' => [
                'Recovery Incentive',
                'Which incentive, if any, should be offered to recover this cart? Avoid incentives for customers '
                . 'who usually buy at full price or who already use a coupon.',
                [
                    'none' => null,
                    'discount' => null,
                    'free_shipping' => null,
                    'gift' => null,
                    'bundle' => 'A bundle with a complementary product',
                ],
            ],
            'messaging_angle' => [
                'Messaging Angle',
                'Which argument should the recovery message focus on?',
                [
                    'price' => 'Good price or savings',
                    'urgency' => 'Limited time',
                    'scarcity' => 'Low stock',
                    'free_shipping' => null,
                    'social_proof' => 'Reviews and popularity',
                    'product_benefit' => 'What the product does for the customer',
                    'reminder' => 'A simple reminder',
                    'none' => null,
                ],
            ],
        ];
    }
}
