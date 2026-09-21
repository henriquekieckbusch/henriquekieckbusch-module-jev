<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Source;

use HenriqueKieckbusch\Jev\Model\Question\Locator;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Option source for UI listing columns and filters, bound to one question via di.xml virtual types.
 */
class GridOptions implements OptionSourceInterface
{
    /**
     * @param Locator $locator
     * @param string $entityType Jev entity type, e.g. "order"
     * @param string $questionCode Question code, e.g. "order_risk"
     */
    public function __construct(
        private readonly Locator $locator,
        private readonly string $entityType,
        private readonly string $questionCode
    ) {
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $question = $this->locator->get($this->entityType, $this->questionCode);
        if ($question === null) {
            return [];
        }
        $options = [];
        foreach ($question->getOptionCodes() as $code) {
            $options[] = ['value' => $code, 'label' => __($question->getOptionLabel($code))];
        }
        return $options;
    }
}
