<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Question;

/**
 * Questions asked about a catalog product: content quality, AI readiness,
 * inventory, merchandising and pricing signals.
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class ProductQuestions extends AbstractQuestions
{
    /**
     * @inheritdoc
     */
    protected function getDefinitions(): array
    {
        return $this->getContentDefinitions()
            + $this->getAiDefinitions()
            + $this->getInventoryDefinitions()
            + $this->getMerchandisingDefinitions()
            + $this->getPricingDefinitions();
    }

    /**
     * Content, data quality and conversion questions.
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, string|null>}>
     */
    private function getContentDefinitions(): array
    {
        $qualityScale = [
            'excellent' => null,
            'good' => null,
            'needs_improvement' => null,
            'poor' => null,
            'missing' => 'Empty or absent',
        ];
        return [
            'product_health' => [
                'Product Health',
                'What is the overall quality of this product listing considering its content, data completeness, '
                . 'configuration and ability to convert visitors into buyers?',
                [
                    'excellent' => null,
                    'good' => null,
                    'acceptable' => null,
                    'poor' => null,
                    'critical' => 'Cannot be sold properly in its current state',
                ],
            ],
            'publication_readiness' => [
                'Publication Readiness',
                'Is this product ready to be published and sold on the storefront?',
                [
                    'ready' => 'Everything needed is in place',
                    'ready_with_warnings' => 'Can be published but some data should be improved',
                    'not_ready' => 'Important content or data is missing',
                ],
            ],
            'title_quality' => [
                'Title Quality',
                'What is the quality of this product\'s name (title)? A good title is descriptive, specific and '
                . 'free of internal codes, all caps or filler words.',
                $qualityScale,
            ],
            'description_quality' => [
                'Description Quality',
                'What is the quality of this product\'s description? Consider length, clarity, benefits, '
                . 'specifications and whether it repeats the title or is empty.',
                [
                    'excellent' => null,
                    'good' => null,
                    'insufficient' => 'Present but too short or too generic',
                    'poor' => null,
                    'missing' => 'Empty or absent',
                ],
            ],
            'image_coverage' => [
                'Image Coverage',
                'Based on the number of images and their labels, are there enough images to present this product '
                . 'properly?',
                [
                    'excellent' => 'Several images with descriptive labels',
                    'sufficient' => null,
                    'needs_more' => 'Only one image or missing labels',
                    'poor' => null,
                    'missing' => 'No images at all',
                ],
            ],
            'attribute_completeness' => [
                'Attribute Completeness',
                'Does this product have values for the attributes that matter for its attribute set? Use the '
                . 'lists of filled and empty attributes.',
                [
                    'complete' => null,
                    'mostly_complete' => null,
                    'incomplete' => null,
                    'severely_incomplete' => null,
                ],
            ],
            'data_consistency' => [
                'Data Consistency',
                'Are the product data consistent with each other? Look for contradictions between the name, '
                . 'description, attributes, categories, price and special price.',
                [
                    'consistent' => null,
                    'minor_issues' => null,
                    'major_issues' => null,
                    'contradictory' => 'Fields clearly contradict each other',
                ],
            ],
            'category_accuracy' => [
                'Category Accuracy',
                'Is this product assigned to the correct categories given its name, description and attributes?',
                [
                    'correct' => null,
                    'probably_correct' => null,
                    'uncertain' => 'Not enough information or no category',
                    'probably_wrong' => null,
                    'wrong' => null,
                ],
            ],
            'conversion_potential' => [
                'Conversion Potential',
                'What is the potential of this product to convert visitors into buyers? Consider content quality, '
                . 'price, reviews, stock availability and recent sales.',
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
                'What is the risk that this product generates returns? Consider product type, sizing or fit '
                . 'attributes, incomplete descriptions and review ratings.',
                [
                    'very_low' => null,
                    'low' => null,
                    'medium' => null,
                    'high' => null,
                    'very_high' => null,
                ],
            ],
            'product_problem' => [
                'Product Problem',
                'What is the main problem of this product listing? Answer "none" if there is no relevant problem.',
                [
                    'price' => null,
                    'content' => 'Name or description',
                    'images' => null,
                    'attributes' => 'Missing or inconsistent attributes',
                    'reviews' => 'Bad or missing reviews',
                    'trust' => 'Information that does not inspire confidence',
                    'availability' => 'Stock or status problems',
                    'shipping' => 'Weight or shipping related data',
                    'positioning' => 'Unclear target audience or value proposition',
                    'category' => 'Wrong or missing categories',
                    'none' => null,
                    'unknown' => 'Not enough information',
                ],
            ],
            'recommended_action' => [
                'Recommended Action',
                'What action should be taken to improve this product?',
                [
                    'none' => null,
                    'improve_content' => 'Rewrite name or description',
                    'add_attributes' => null,
                    'add_images' => null,
                    'improve_reviews' => 'Collect more or better reviews',
                    'change_price' => null,
                    'improve_positioning' => null,
                    'change_category' => null,
                    'promote' => 'Give it more visibility',
                    'discontinue' => 'Stop selling it',
                ],
            ],
        ];
    }

    /**
     * AI agent readiness questions.
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, string|null>}>
     */
    private function getAiDefinitions(): array
    {
        return [
            'ai_understanding' => [
                'AI Understanding',
                'How easily can an AI shopping agent understand what this product is, who it is for and what makes '
                . 'it different, using only the text data provided?',
                [
                    'excellent' => null,
                    'good' => null,
                    'poor' => null,
                    'unusable' => 'The data does not explain the product',
                ],
            ],
            'ai_recommendation_readiness' => [
                'AI Recommendation Readiness',
                'Does this product have enough structured information (attributes, description, categories, '
                . 'price, availability) to be correctly recommended by an AI agent?',
                [
                    'yes' => null,
                    'mostly' => null,
                    'insufficient' => null,
                    'no' => null,
                ],
            ],
            'ai_visibility' => [
                'AI Visibility',
                'What is the potential of this product to be discovered and recommended by AI agents, considering '
                . 'the descriptiveness of its name, description, attributes and categories?',
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
     * Inventory questions.
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, string|null>}>
     */
    private function getInventoryDefinitions(): array
    {
        return [
            'stock_level' => [
                'Stock Level',
                'Is the current stock level adequate? Compare the quantity in stock with the quantities sold in '
                . 'the last 30, 90 and 365 days and the precomputed days of stock left.',
                [
                    'critical_low' => 'Will run out very soon or already out',
                    'low' => null,
                    'adequate' => null,
                    'high' => null,
                    'excessive' => 'Far more stock than the sales pace justifies',
                ],
            ],
            'stockout_risk' => [
                'Stockout Risk',
                'What is the risk that this product runs out of stock soon, given the stock quantity and the '
                . 'recent sales pace?',
                [
                    'very_low' => null,
                    'low' => null,
                    'medium' => null,
                    'high' => null,
                    'critical' => 'Already out of stock or about to be',
                ],
            ],
            'reorder_decision' => [
                'Reorder Decision',
                'Should more units of this product be purchased from the supplier now?',
                [
                    'no' => null,
                    'probably_no' => null,
                    'probably_yes' => null,
                    'yes' => null,
                    'urgent' => 'Sales will be lost without an immediate reorder',
                ],
            ],
            'dead_stock_risk' => [
                'Dead Stock Risk',
                'What is the risk that this product is or becomes dead stock (units that will not sell)? '
                . 'Consider stock quantity versus sales pace and time since the last sale.',
                [
                    'very_low' => null,
                    'low' => null,
                    'medium' => null,
                    'high' => null,
                    'critical' => 'Not selling at all with stock on hand',
                ],
            ],
            'inventory_action' => [
                'Inventory Action',
                'What should be done with the inventory of this product?',
                [
                    'maintain' => 'Keep as is',
                    'promote' => 'Increase visibility to sell faster',
                    'discount' => 'Reduce the price to sell faster',
                    'bundle' => 'Sell together with other products',
                    'liquidate' => 'Clear the remaining units',
                    'discontinue' => 'Stop selling it',
                ],
            ],
        ];
    }

    /**
     * Merchandising questions.
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, string|null>}>
     */
    private function getMerchandisingDefinitions(): array
    {
        $opportunity = [
            'none' => null,
            'low' => null,
            'medium' => null,
            'high' => null,
            'very_high' => null,
        ];
        return [
            'promotion_priority' => [
                'Promotion Priority',
                'What priority should this product have in promotions and featured placements? Consider sales, '
                . 'reviews, stock and content quality.',
                [
                    'none' => null,
                    'low' => null,
                    'normal' => null,
                    'high' => null,
                    'very_high' => null,
                ],
            ],
            'merchandising_strategy' => [
                'Merchandising Strategy',
                'Which merchandising strategy suits this product best?',
                [
                    'best_sellers' => 'Feature among best sellers',
                    'new_arrivals' => 'Feature as a new arrival',
                    'discounts' => 'Feature in deals and discounts',
                    'premium' => 'Position as a premium product',
                    'similar_products' => 'Show next to similar products',
                    'cross_sell' => 'Show as a complement to other products',
                    'upsell' => 'Show as a better alternative to cheaper products',
                    'clearance' => 'Feature in clearance',
                    'personalized' => 'Recommend based on customer behavior',
                ],
            ],
            'cross_sell_opportunity' => [
                'Cross-sell Opportunity',
                'Is there a relevant opportunity to cross-sell this product together with complementary products, '
                . 'given what it is and its current linked products?',
                $opportunity,
            ],
            'upsell_opportunity' => [
                'Upsell Opportunity',
                'Is there a relevant opportunity to upsell from this product to a higher value alternative, '
                . 'given its price positioning and its current linked products?',
                $opportunity,
            ],
        ];
    }

    /**
     * Pricing questions answerable from internal data only (no market data is sent).
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, string|null>}>
     */
    private function getPricingDefinitions(): array
    {
        return [
            'discount_opportunity' => [
                'Discount Opportunity',
                'Is there a relevant opportunity to apply a discount to this product? Consider slow sales, high '
                . 'stock, time since the last sale, existing special price and margin when cost is present.',
                [
                    'none' => null,
                    'low' => null,
                    'medium' => null,
                    'high' => null,
                ],
            ],
            'pricing_action' => [
                'Pricing Action',
                'Which pricing action fits this product best, using only the store\'s own data (sales pace, stock, '
                . 'reviews, cost and current special price)?',
                [
                    'maintain' => null,
                    'increase' => 'Selling fast with low stock and good reviews',
                    'decrease' => 'Permanently lower the price',
                    'discount' => 'Temporary discount',
                    'promotion' => 'Run a promotion without cutting the base price',
                    'clearance' => 'Clear remaining stock',
                ],
            ],
        ];
    }
}
