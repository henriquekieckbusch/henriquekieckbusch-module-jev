<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Context;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;
use Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory as SourceItemCollectionFactory;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;

/**
 * Reads a product's stock level and recent sales pace.
 */
class ProductInventory
{
    /**
     * @param SourceItemCollectionFactory $sourceItemCollectionFactory
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param Age $age
     */
    public function __construct(
        private readonly SourceItemCollectionFactory $sourceItemCollectionFactory,
        private readonly GetProductSalableQtyInterface $getProductSalableQty,
        private readonly DefaultStockProviderInterface $defaultStockProvider,
        private readonly OrderItemCollectionFactory $orderItemCollectionFactory,
        private readonly Age $age
    ) {
    }

    /**
     * Collect the product's salable quantity and number of stock sources.
     *
     * @param ProductInterface $product
     * @return array<string,mixed>
     */
    public function getInventoryData(ProductInterface $product): array
    {
        try {
            $qty = $this->getProductSalableQty->execute(
                (string)$product->getSku(),
                $this->defaultStockProvider->getId()
            );
        } catch (\Exception $e) {
            $qty = null;
        }
        $sourceCount = $this->sourceItemCollectionFactory->create()
            ->addFieldToFilter('sku', $product->getSku())
            ->getSize();
        return [
            'salable_qty' => $qty,
            'source_count' => $sourceCount,
        ];
    }

    /**
     * Collect the product's quantity sold over the last 30, 90 and 365 days.
     *
     * @param ProductInterface $product
     * @return array<string,mixed>
     */
    public function getSalesData(ProductInterface $product): array
    {
        return [
            'qty_sold_last_30d' => $this->getQtySold((string)$product->getSku(), 30),
            'qty_sold_last_90d' => $this->getQtySold((string)$product->getSku(), 90),
            'qty_sold_last_365d' => $this->getQtySold((string)$product->getSku(), 365),
        ];
    }

    /**
     * Sum the quantity of the SKU sold in the given number of past days.
     *
     * @param string $sku
     * @param int $days
     * @return float
     */
    private function getQtySold(string $sku, int $days): float
    {
        $collection = $this->orderItemCollectionFactory->create();
        $collection->addFieldToFilter('sku', $sku)
            ->addFieldToFilter('created_at', ['gteq' => $this->age->daysAgo($days)])
            ->addFieldToFilter('parent_item_id', ['null' => true]);
        $collection->getSelect()->reset(Select::COLUMNS)
            ->columns(['qty' => new Expression('SUM(qty_ordered)')]);
        return (float)($collection->getFirstItem()->getData('qty') ?? 0);
    }
}
