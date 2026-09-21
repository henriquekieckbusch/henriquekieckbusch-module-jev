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
 * Refresh the Jev analysis of a product and return to the product edit page.
 */
class Product extends AbstractRefresh
{
    /**
     * @inheritdoc
     */
    protected function getType(): string
    {
        return 'product';
    }

    /**
     * @inheritdoc
     */
    protected function redirectBack(int $entityId, Redirect $redirect): Redirect
    {
        return $redirect->setPath('catalog/product/edit', ['id' => $entityId]);
    }
}
