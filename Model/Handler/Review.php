<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Handler;

use HenriqueKieckbusch\Jev\Model\Context\Age;
use HenriqueKieckbusch\Jev\Model\Question\QuestionsInterface;
use HenriqueKieckbusch\Jev\Model\Question\ReviewQuestions;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Review\Model\Review as ReviewModel;
use Magento\Review\Model\ReviewFactory;
use Magento\Review\Model\ResourceModel\Rating\Option\Vote\CollectionFactory as VoteCollectionFactory;

/**
 * Builds the moderation context of a product review and writes Jev's answers
 * with a direct SQL update, so review_detail / review_store and the review's
 * own save events are never touched (no recursion risk).
 */
class Review implements HandlerInterface
{
    private const TABLE = 'review';

    /**
     * @param ReviewFactory $reviewFactory
     * @param VoteCollectionFactory $voteCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ResourceConnection $resourceConnection
     * @param Age $age
     * @param ReviewQuestions $questions
     */
    public function __construct(
        private readonly ReviewFactory $reviewFactory,
        private readonly VoteCollectionFactory $voteCollectionFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ResourceConnection $resourceConnection,
        private readonly Age $age,
        private readonly ReviewQuestions $questions
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return 'review';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return 'Review';
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
        $review = $this->reviewFactory->create()->load($entityId);
        if (!$review->getId()) {
            throw new NoSuchEntityException(__('The review with id "%1" does not exist.', $entityId));
        }
        return [
            'review' => [
                'title' => $review->getTitle(),
                'nickname' => $review->getNickname(),
                'detail' => mb_substr((string)$review->getDetail(), 0, 2000),
                'created_days_ago' => $this->age->daysSince($review->getCreatedAt()),
                'is_guest' => !$review->getCustomerId(),
            ],
            'product' => $this->getProductData((int)$review->getEntityPkValue()),
            'ratings' => $this->getRatings($entityId),
            'has_purchased' => $review->getCustomerId()
                ? $this->hasPurchased((int)$review->getCustomerId(), (int)$review->getEntityPkValue())
                : false,
        ];
    }

    /**
     * Look up the reviewed product's name and SKU, or an empty array if it no longer exists.
     *
     * @param int $productId
     * @return array<string, mixed>
     */
    private function getProductData(int $productId): array
    {
        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return [];
        }
        return ['name' => $product->getName(), 'sku' => $product->getSku()];
    }

    /**
     * List the rating codes and percentages voted on this review.
     *
     * @param int $reviewId
     * @return array<int, array{code: string, percent: int}>
     */
    private function getRatings(int $reviewId): array
    {
        $votes = $this->voteCollectionFactory->create()->setReviewFilter($reviewId)->addRatingInfo()->load();
        $ratings = [];
        foreach ($votes as $vote) {
            $ratings[] = ['code' => (string)$vote->getRatingCode(), 'percent' => (int)$vote->getPercent()];
        }
        return $ratings;
    }

    /**
     * Whether the reviewer has ever bought this product (a proxy for review authenticity).
     *
     * @param int $customerId
     * @param int $productId
     * @return bool
     */
    private function hasPurchased(int $customerId, int $productId): bool
    {
        $connection = $this->resourceConnection->getConnection('sales');
        $select = $connection->select()
            ->from(['item' => $this->resourceConnection->getTableName('sales_order_item')], ['1'])
            ->joinInner(
                ['order' => $this->resourceConnection->getTableName('sales_order')],
                'order.entity_id = item.order_id',
                []
            )
            ->where('order.customer_id = ?', $customerId)
            ->where('item.product_id = ?', $productId)
            ->limit(1);
        return (bool)$connection->fetchOne($select);
    }

    /**
     * @inheritdoc
     */
    public function persist(int $entityId, array $choices): void
    {
        if ($choices === []) {
            return;
        }
        $data = [];
        foreach ($choices as $code => $choice) {
            $data['jev_' . $code] = $choice;
        }
        $connection = $this->resourceConnection->getConnection();
        $connection->update(
            $this->resourceConnection->getTableName(self::TABLE),
            $data,
            ['review_id = ?' => $entityId]
        );
    }
}
