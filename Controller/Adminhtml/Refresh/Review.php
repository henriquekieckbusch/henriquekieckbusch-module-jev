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
 * Refresh the Jev analysis of a review and return to the review edit page.
 */
class Review extends AbstractRefresh
{
    /**
     * @inheritdoc
     */
    protected function getType(): string
    {
        return 'review';
    }

    /**
     * @inheritdoc
     */
    protected function redirectBack(int $entityId, Redirect $redirect): Redirect
    {
        return $redirect->setPath('review/product/edit', ['id' => $entityId]);
    }
}
