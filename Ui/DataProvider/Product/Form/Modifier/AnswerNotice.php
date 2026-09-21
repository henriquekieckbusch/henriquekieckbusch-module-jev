<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Ui\DataProvider\Product\Form\Modifier;

use HenriqueKieckbusch\Jev\Model\Question\ProductQuestions;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;

/**
 * Replaces the static "note" of every Jev select field with the longer description of the
 * product's currently saved answer (e.g. field shows "Good", notice explains what "Good" means).
 * Runs after the core Eav modifier (sortOrder 10), which is where those fields are built.
 */
class AnswerNotice extends AbstractModifier
{
    /**
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     * @param ProductQuestions $questions
     */
    public function __construct(
        private readonly LocatorInterface $locator,
        private readonly ArrayManager $arrayManager,
        private readonly ProductQuestions $questions
    ) {
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data): array
    {
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta): array
    {
        $product = $this->locator->getProduct();
        foreach ($this->questions->getQuestions() as $question) {
            $value = (string)$product->getData($question->getAttributeCode());
            $description = $value === '' ? null : $question->getOptionDescription($value);
            $meta = $this->setNotice($meta, $question->getAttributeCode(), $description);
        }
        return $meta;
    }

    /**
     * Overwrite the "notice" of one field's meta, wherever it was placed by the Eav modifier.
     * Always sets it (to the description, or to null), so no stale note can be left behind
     * when the current answer has no longer description of its own.
     *
     * @param array $meta
     * @param string $attributeCode
     * @param string|null $description
     * @return array
     */
    private function setNotice(array $meta, string $attributeCode, ?string $description): array
    {
        $path = $this->arrayManager->findPath($attributeCode, $meta, null, 'children');
        if ($path === null) {
            return $meta;
        }
        $notice = $description === null ? null : __($description);
        return $this->arrayManager->merge($path . static::META_CONFIG_PATH, $meta, ['notice' => $notice]);
    }
}
