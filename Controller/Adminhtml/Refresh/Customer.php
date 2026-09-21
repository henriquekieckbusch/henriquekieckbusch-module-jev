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
 * Refresh the Jev analysis of a customer and return to the customer edit page.
 */
class Customer extends AbstractRefresh
{
    /**
     * @inheritdoc
     */
    protected function getType(): string
    {
        return 'customer';
    }

    /**
     * @inheritdoc
     */
    protected function redirectBack(int $entityId, Redirect $redirect): Redirect
    {
        return $redirect->setPath('customer/index/edit', ['id' => $entityId]);
    }
}
