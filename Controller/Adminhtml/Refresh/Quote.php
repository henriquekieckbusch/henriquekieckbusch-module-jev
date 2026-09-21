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
 * Refresh the Jev analysis of an abandoned cart and return to the Abandoned Carts report.
 */
class Quote extends AbstractRefresh
{
    /**
     * @inheritdoc
     */
    protected function getType(): string
    {
        return 'quote';
    }

    /**
     * @inheritdoc
     */
    protected function redirectBack(int $entityId, Redirect $redirect): Redirect
    {
        return $redirect->setPath('reports/report_shopcart/abandoned');
    }
}
