<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Controller\Adminhtml\Refresh;

use HenriqueKieckbusch\Jev\Controller\Adminhtml\AbstractRefresh;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Refresh the Jev analysis of an order and return to the order view.
 */
class Order extends AbstractRefresh
{
    /**
     * @inheritdoc
     */
    protected function getType(): string
    {
        return 'order';
    }

    /**
     * @inheritdoc
     */
    protected function redirectBack(int $entityId, Redirect $redirect): Redirect
    {
        return $redirect->setPath('sales/order/view', ['order_id' => $entityId, 'active_tab' => 'jev']);
    }
}
