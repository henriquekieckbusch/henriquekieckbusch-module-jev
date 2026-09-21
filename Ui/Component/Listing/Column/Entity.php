<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Links each activity row to the admin page of the analyzed entity.
 */
class Entity extends Column
{
    private const PATHS = [
        'order' => ['sales/order/view', 'order_id'],
        'customer' => ['customer/index/edit', 'id'],
        'product' => ['catalog/product/edit', 'id'],
        'review' => ['review/product/edit', 'id'],
        'quote' => ['reports/report_shopcart/abandoned', null],
    ];

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            $item[$name] = $this->buildLink((string)$item['entity_type'], (int)$item['entity_id']);
        }
        return $dataSource;
    }

    /**
     * Build the label and admin URL for the given entity.
     *
     * @param string $type
     * @param int $entityId
     * @return array{label: string, href: string}
     */
    private function buildLink(string $type, int $entityId): array
    {
        [$path, $param] = self::PATHS[$type] ?? ['adminhtml/dashboard', null];
        return [
            'label' => sprintf('#%d', $entityId),
            'href' => $this->urlBuilder->getUrl($path, $param !== null ? [$param => $entityId] : []),
        ];
    }
}
