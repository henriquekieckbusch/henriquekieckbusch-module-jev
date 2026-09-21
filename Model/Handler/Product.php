<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Handler;

use HenriqueKieckbusch\Jev\Model\Context\Age;
use HenriqueKieckbusch\Jev\Model\Context\ProductContent;
use HenriqueKieckbusch\Jev\Model\Context\ProductInventory;
use HenriqueKieckbusch\Jev\Model\ProductAttributeWriter;
use HenriqueKieckbusch\Jev\Model\Question\ProductQuestions;
use HenriqueKieckbusch\Jev\Model\Question\QuestionsInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

/**
 * Builds the content, inventory and merchandising context of a product and
 * stores Jev's answers as product EAV attributes, without a full product save.
 */
class Product implements HandlerInterface
{
    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ProductContent $content
     * @param ProductInventory $inventory
     * @param ProductAttributeWriter $attributeWriter
     * @param Age $age
     * @param ProductQuestions $questions
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductContent $content,
        private readonly ProductInventory $inventory,
        private readonly ProductAttributeWriter $attributeWriter,
        private readonly Age $age,
        private readonly ProductQuestions $questions
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return 'product';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return 'Product';
    }

    /**
     * @inheritdoc
     */
    public function getQuestions(): QuestionsInterface
    {
        return $this->questions;
    }

    /**
     * @inheritdoc
     */
    public function buildContext(int $entityId): array
    {
        $product = $this->productRepository->getById($entityId);
        return [
            'product' => $this->getProductData($product),
            'attributes' => $this->content->getAttributeData($product),
            'categories' => $this->content->getCategoryNames($product),
            'images' => $this->content->getImageData($product),
            'inventory' => $this->inventory->getInventoryData($product),
            'sales' => $this->inventory->getSalesData($product),
            'reviews' => $this->content->getReviewData($product),
            'related_products' => $this->getLinkedProductCounts($product),
        ];
    }

    /**
     * Count related, up-sell and cross-sell products linked to the product.
     *
     * @param ProductInterface $product
     * @return array<string,int>
     */
    private function getLinkedProductCounts(ProductInterface $product): array
    {
        /** @var ProductModel $product */
        return [
            'related_count' => count($product->getRelatedProductIds() ?? []),
            'upsell_count' => count($product->getUpSellProductIds() ?? []),
            'crosssell_count' => count($product->getCrossSellProductIds() ?? []),
        ];
    }

    /**
     * Collect the core product fields used as Jev context.
     *
     * @param ProductInterface $product
     * @return array<string,mixed>
     */
    private function getProductData(ProductInterface $product): array
    {
        return [
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'type' => $product->getTypeId(),
            'attribute_set' => $this->content->getAttributeSetName((int)$product->getAttributeSetId()),
            'status' => (int)$product->getStatus() === Status::STATUS_ENABLED ? 'enabled' : 'disabled',
            'visibility' => $product->getVisibility(),
            'price' => (float)$product->getPrice(),
            'special_price' => $product->getSpecialPrice() !== null ? (float)$product->getSpecialPrice() : null,
            'cost' => $product->getCost() !== null ? (float)$product->getCost() : null,
            'weight' => $product->getWeight() !== null ? (float)$product->getWeight() : null,
            'description' => mb_substr((string)$product->getDescription(), 0, 2000),
            'short_description' => mb_substr((string)$product->getShortDescription(), 0, 500),
            'url_key' => $product->getUrlKey(),
            'meta_title' => $product->getMetaTitle(),
            'meta_description' => $product->getMetaDescription(),
            'age_days' => $this->age->daysSince($product->getCreatedAt()),
            'updated_days_ago' => $this->age->daysSince($product->getUpdatedAt()),
        ];
    }

    /**
     * @inheritdoc
     */
    public function persist(int $entityId, array $choices): void
    {
        $attributeValues = [];
        foreach ($choices as $code => $choice) {
            $attributeValues['jev_' . $code] = $choice;
        }
        $this->attributeWriter->write($entityId, $attributeValues);
    }
}
