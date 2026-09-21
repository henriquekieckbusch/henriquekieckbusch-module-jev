<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model;

use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ResourceModel\Product\Action;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Indexer\CacheContext;

/**
 * Writes Jev answers to product EAV attributes without a full product save,
 * then cleans the product cache tag (updateAttributes fires no save event).
 */
class ProductAttributeWriter
{
    /**
     * @param Action $productAction
     * @param CacheContext $cacheContext
     * @param EventManager $eventManager
     */
    public function __construct(
        private readonly Action $productAction,
        private readonly CacheContext $cacheContext,
        private readonly EventManager $eventManager
    ) {
    }

    /**
     * Write the given Jev answers as product EAV attribute values.
     *
     * @param int $productId
     * @param array<string,string> $attributeValues Attribute code (with "jev_" prefix) => value
     * @return void
     */
    public function write(int $productId, array $attributeValues): void
    {
        if ($attributeValues === []) {
            return;
        }
        $this->productAction->updateAttributes([$productId], $attributeValues, 0);
        $this->cleanCache($productId);
    }

    /**
     * Clean the product cache tag directly. Product\Action::updateAttributes fires no
     * catalog_product_save_* event, and the core cache-flush plugin only runs in the
     * adminhtml area, so cron and CLI updates would otherwise leave stale cached pages.
     *
     * @param int $productId
     * @return void
     */
    private function cleanCache(int $productId): void
    {
        $this->cacheContext->registerEntities(ProductModel::CACHE_TAG, [$productId]);
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
    }
}
