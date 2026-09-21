<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Source;

use HenriqueKieckbusch\Jev\Model\Question\Locator;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * EAV source model for Jev select attributes. The options come from the question
 * whose attribute code matches the attribute this source is attached to; the stored
 * value is the option code itself (varchar), never a numeric option id.
 *
 * Configured per entity type through di.xml virtual types (argument "entityType").
 */
class Options extends AbstractSource
{
    /**
     * @param Locator $locator
     * @param string $entityType Jev entity type the attribute belongs to (customer, product)
     */
    public function __construct(
        private readonly Locator $locator,
        private readonly string $entityType
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [['value' => '', 'label' => __('-- Not analyzed --')]];
            $question = $this->locator->getByAttributeCode($this->entityType, $this->getAttributeCode());
            if ($question !== null) {
                foreach ($question->getOptionCodes() as $code) {
                    $this->_options[] = ['value' => $code, 'label' => __($question->getOptionLabel($code))];
                }
            }
        }
        return $this->_options;
    }

    /**
     * Attribute code, or an empty string when the source has not been attached to an attribute yet.
     *
     * @return string
     */
    private function getAttributeCode(): string
    {
        try {
            return (string)$this->getAttribute()->getAttributeCode();
        } catch (\Throwable $e) {
            return '';
        }
    }
}
