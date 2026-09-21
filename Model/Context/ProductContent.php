<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Context;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Review\Model\AppendSummaryData;

/**
 * Reads a product's content quality signals: attribute set, custom attributes,
 * categories, images and review summary.
 */
class ProductContent
{
    /**
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param AppendSummaryData $appendSummaryData
     */
    public function __construct(
        private readonly AttributeSetRepositoryInterface $attributeSetRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly AppendSummaryData $appendSummaryData
    ) {
    }

    /**
     * Resolve the attribute set name, or an empty string if it no longer exists.
     *
     * @param int $attributeSetId
     * @return string
     */
    public function getAttributeSetName(int $attributeSetId): string
    {
        try {
            return (string)$this->attributeSetRepository->get($attributeSetId)->getAttributeSetName();
        } catch (NoSuchEntityException $e) {
            return '';
        }
    }

    /**
     * Every user-defined attribute with a non-empty value: label => display value.
     *
     * @param ProductInterface $product
     * @return array<string,string>
     */
    public function getAttributeData(ProductInterface $product): array
    {
        /** @var ProductModel $product */
        $data = [];
        foreach ($product->getAttributes() as $attribute) {
            $code = $attribute->getAttributeCode();
            if (!$attribute->getIsUserDefined() || str_starts_with($code, 'jev_')) {
                continue;
            }
            $value = $product->getData($code);
            if ($value === null || $value === '') {
                continue;
            }
            $display = $this->getDisplayValue($attribute, $product);
            if ($display !== '') {
                $data[(string)$attribute->getStoreLabel()] = mb_substr($display, 0, 300);
            }
        }
        return $data;
    }

    /**
     * Render the attribute's frontend value as a string, flattening arrays (e.g. multiselect).
     *
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @param ProductInterface $product
     * @return string
     */
    private function getDisplayValue(
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute,
        ProductInterface $product
    ): string {
        try {
            $rawDisplay = $attribute->getFrontend()->getValue($product);
        } catch (\Exception $e) {
            return '';
        }
        return is_array($rawDisplay) ? implode(', ', array_map('strval', $rawDisplay)) : (string)$rawDisplay;
    }

    /**
     * List the names of the categories the product belongs to.
     *
     * @param ProductInterface $product
     * @return string[]
     */
    public function getCategoryNames(ProductInterface $product): array
    {
        $names = [];
        foreach ((array)$product->getCategoryIds() as $categoryId) {
            try {
                $names[] = $this->categoryRepository->get((int)$categoryId)->getName();
            } catch (NoSuchEntityException $e) {
                continue;
            }
        }
        return $names;
    }

    /**
     * Count the product's media gallery images and how many have a label.
     *
     * @param ProductInterface $product
     * @return array<string,mixed>
     */
    public function getImageData(ProductInterface $product): array
    {
        $gallery = $product->getMediaGalleryEntries() ?? [];
        $labeled = 0;
        foreach ($gallery as $entry) {
            if ((string)$entry->getLabel() !== '') {
                $labeled++;
            }
        }
        return ['image_count' => count($gallery), 'labeled_image_count' => $labeled];
    }

    /**
     * Collect the product's review count and rating summary.
     *
     * @param ProductInterface $product
     * @return array<string,mixed>
     */
    public function getReviewData(ProductInterface $product): array
    {
        /** @var ProductModel $product */
        $this->appendSummaryData->execute($product, 0, 'product');
        return [
            'reviews_count' => (int)$product->getData('reviews_count') ?: 0,
            'rating_summary_percent' => (int)$product->getData('rating_summary') ?: 0,
        ];
    }
}
