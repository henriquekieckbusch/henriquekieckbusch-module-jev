<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Question;

/**
 * Questions asked about a product review (moderation support).
 */
class ReviewQuestions extends AbstractQuestions
{
    /**
     * @inheritdoc
     */
    protected function getDefinitions(): array
    {
        return [
            'review_authenticity' => [
                'Review Authenticity',
                'How likely is this review to be genuine? Consider whether the reviewer bought the product, the '
                . 'reviewer history, the writing style and how specific the text is to the product.',
                [
                    'very_likely' => 'Clearly written by a real buyer',
                    'likely' => null,
                    'uncertain' => null,
                    'suspicious' => null,
                    'very_likely_fake' => 'Generic, promotional or spam-like',
                ],
            ],
            'review_quality' => [
                'Review Quality',
                'How useful is this review for other shoppers?',
                [
                    'excellent' => 'Detailed, specific and balanced',
                    'useful' => null,
                    'low_value' => 'Very short or vague',
                    'useless' => 'Says nothing about the product or is spam',
                ],
            ],
            'product_issue' => [
                'Product Issue',
                'Does this review indicate a real problem with the product (defect, wrong description, sizing, '
                . 'quality)?',
                [
                    'no' => null,
                    'possibly' => null,
                    'likely' => null,
                    'yes' => 'Describes a concrete product problem',
                ],
            ],
            'review_sentiment' => [
                'Review Sentiment',
                'What is the overall sentiment of this review towards the product?',
                [
                    'positive' => null,
                    'neutral' => null,
                    'mixed' => 'Both clear praise and clear complaints',
                    'negative' => null,
                ],
            ],
            'review_action' => [
                'Review Action',
                'What should the moderator do with this review?',
                [
                    'publish' => null,
                    'publish_and_monitor' => 'Publish but watch for similar reviews',
                    'moderate' => 'Edit or ask the reviewer for changes before publishing',
                    'reject' => null,
                    'escalate' => 'Send to a manager, for example a serious product or safety complaint',
                ],
            ],
        ];
    }
}
