<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\ResourceModel;

use HenriqueKieckbusch\Jev\Model\Context\Age;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;

/**
 * Aggregated order history of one customer (by id) or of one e-mail address (guests),
 * with the derived figures Jev cannot compute reliably itself.
 */
class CustomerHistory
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param Age $age
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly Age $age
    ) {
    }

    /**
     * Order statistics of a registered customer.
     *
     * @param int $customerId
     * @param int|null $excludeOrderId Order being analyzed, left out of "previous" figures
     * @return array<string, mixed>
     */
    public function byCustomerId(int $customerId, ?int $excludeOrderId = null): array
    {
        return $this->aggregate('customer_id = ?', $customerId, $excludeOrderId);
    }

    /**
     * Order statistics of an e-mail address (useful for guests).
     *
     * @param string $email
     * @param int|null $excludeOrderId
     * @return array<string, mixed>
     */
    public function byEmail(string $email, ?int $excludeOrderId = null): array
    {
        return $this->aggregate('customer_email = ?', $email, $excludeOrderId);
    }

    /**
     * Run the order aggregate query for the given filter condition.
     *
     * @param string $condition
     * @param int|string $value
     * @param int|null $excludeOrderId
     * @return array<string, mixed>
     */
    private function aggregate(string $condition, int|string $value, ?int $excludeOrderId): array
    {
        $connection = $this->resourceConnection->getConnection('sales');
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('sales_order'), $this->getColumns())
            ->where($condition, $value);
        if ($excludeOrderId !== null) {
            $select->where('entity_id <> ?', $excludeOrderId);
        }
        $row = $connection->fetchRow($select) ?: [];
        return $this->format($row);
    }

    /**
     * Build the aggregate column expressions for the order history query.
     *
     * @return array<string, Expression|string>
     */
    private function getColumns(): array
    {
        $d1 = $this->age->hoursAgo(24);
        $d7 = $this->age->daysAgo(7);
        $d90 = $this->age->daysAgo(90);
        $d365 = $this->age->daysAgo(365);
        return [
            'orders_count' => new Expression('COUNT(*)'),
            'total_spent' => new Expression('COALESCE(SUM(base_grand_total), 0)'),
            'first_order_at' => new Expression('MIN(created_at)'),
            'last_order_at' => new Expression('MAX(created_at)'),
            'canceled_count' => new Expression("SUM(state = 'canceled')"),
            'closed_count' => new Expression("SUM(state = 'closed')"),
            'refunded_amount' => new Expression('COALESCE(SUM(base_total_refunded), 0)'),
            'orders_with_coupon' => new Expression("SUM(coupon_code IS NOT NULL AND coupon_code <> '')"),
            'distinct_coupons' => new Expression("COUNT(DISTINCT NULLIF(coupon_code, ''))"),
            'total_discount' => new Expression('COALESCE(SUM(ABS(base_discount_amount)), 0)'),
            'orders_last_24h' => new Expression('SUM(created_at >= ' . $this->quote($d1) . ')'),
            'orders_last_7d' => new Expression('SUM(created_at >= ' . $this->quote($d7) . ')'),
            'orders_last_90d' => new Expression('SUM(created_at >= ' . $this->quote($d90) . ')'),
            'orders_last_365d' => new Expression('SUM(created_at >= ' . $this->quote($d365) . ')'),
        ];
    }

    /**
     * Quote a value for direct interpolation into a SQL expression.
     *
     * @param string $value
     * @return string
     */
    private function quote(string $value): string
    {
        return $this->resourceConnection->getConnection('sales')->quote($value);
    }

    /**
     * Cast and derive ratios / averages so the model does not have to do arithmetic.
     *
     * @param array<string,mixed> $row
     * @return array<string, mixed>
     */
    private function format(array $row): array
    {
        $count = (int)($row['orders_count'] ?? 0);
        $spent = round((float)($row['total_spent'] ?? 0), 2);
        $discount = round((float)($row['total_discount'] ?? 0), 2);
        $withCoupon = (int)($row['orders_with_coupon'] ?? 0);
        $problem = (int)($row['canceled_count'] ?? 0) + (int)($row['closed_count'] ?? 0);
        $firstAt = $row['first_order_at'] ?? null;
        $lastAt = $row['last_order_at'] ?? null;
        $spanDays = $count > 1 ? ($this->age->daysSince($firstAt) ?? 0) - ($this->age->daysSince($lastAt) ?? 0) : 0;
        return [
            'orders_count' => $count,
            'total_spent' => $spent,
            'average_order_value' => $count > 0 ? round($spent / $count, 2) : 0,
            'first_order_at' => $firstAt,
            'last_order_at' => $lastAt,
            'days_since_first_order' => $this->age->daysSince($firstAt),
            'days_since_last_order' => $this->age->daysSince($lastAt),
            'average_days_between_orders' => $count > 1 ? (int)round($spanDays / ($count - 1)) : null,
            'orders_last_24h' => (int)($row['orders_last_24h'] ?? 0),
            'orders_last_7d' => (int)($row['orders_last_7d'] ?? 0),
            'orders_last_90d' => (int)($row['orders_last_90d'] ?? 0),
            'orders_last_365d' => (int)($row['orders_last_365d'] ?? 0),
            'canceled_orders' => (int)($row['canceled_count'] ?? 0),
            'closed_or_refunded_orders' => (int)($row['closed_count'] ?? 0),
            'refunded_amount' => round((float)($row['refunded_amount'] ?? 0), 2),
            'problem_orders_ratio' => $count > 0 ? round($problem / $count, 2) : 0,
            'orders_with_coupon' => $withCoupon,
            'coupon_usage_ratio' => $count > 0 ? round($withCoupon / $count, 2) : 0,
            'distinct_coupons_used' => (int)($row['distinct_coupons'] ?? 0),
            'total_discount' => $discount,
            'discount_to_spend_ratio' => $spent > 0 ? round($discount / $spent, 2) : 0,
        ];
    }
}
