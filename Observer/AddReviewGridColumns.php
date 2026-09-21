<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Review\Block\Adminhtml\Grid;

/**
 * Adds the Jev columns to the legacy review grid (it has no ui_component / listing XML,
 * so this is the only supported extension point: backend_block_widget_grid_prepare_grid_before,
 * fired right after core columns are prepared and before the collection is loaded).
 */
class AddReviewGridColumns implements ObserverInterface
{
    private const COLUMNS = ['jev_review_action', 'jev_review_authenticity'];

    /**
     * Add the Jev columns to the legacy review grid once it is available.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $grid = $observer->getEvent()->getData('grid');
        if (!$grid instanceof Grid) {
            return;
        }
        $this->addColumns($grid);
    }

    /**
     * Register the Jev columns on the given grid.
     *
     * @param Grid $grid
     * @return void
     */
    private function addColumns(Grid $grid): void
    {
        $grid->addColumnAfter('jev_review_action', [
            'header' => __('Jev: Action'),
            'align' => 'left',
            'index' => 'jev_review_action',
            'width' => '120px',
        ], 'status');
        $grid->addColumnAfter('jev_review_authenticity', [
            'header' => __('Jev: Authenticity'),
            'align' => 'left',
            'index' => 'jev_review_authenticity',
            'width' => '120px',
        ], 'jev_review_action');
        $grid->sortColumnsByOrder();
    }
}
