<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Reports\Block\Adminhtml\Shopcart\Abandoned\Grid;

/**
 * Adds the Jev recovery columns to the legacy Abandoned Carts report grid (built with
 * _prepareColumns(), no ui_component / listing XML available).
 */
class AddAbandonedCartGridColumns implements ObserverInterface
{
    /**
     * Add the Jev recovery columns to the abandoned carts grid once it is available.
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
        $grid->addColumn('jev_recovery_priority', [
            'header' => __('Jev: Recovery Priority'),
            'align' => 'left',
            'index' => 'jev_recovery_priority',
            'width' => '140px',
        ]);
        $grid->addColumn('jev_recovery_strategy', [
            'header' => __('Jev: Recovery Strategy'),
            'align' => 'left',
            'index' => 'jev_recovery_strategy',
            'width' => '140px',
        ]);
        $grid->sortColumnsByOrder();
    }
}
