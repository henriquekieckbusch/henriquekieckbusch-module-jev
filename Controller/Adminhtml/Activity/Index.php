<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Controller\Adminhtml\Activity;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;

/**
 * Reports > Marketing > Jev Activity: every analyzed entity with tokens used and errors.
 */
class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'HenriqueKieckbusch_Jev::activity';

    /**
     * Render the Jev Activity grid page.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        /** @var Page $page */
        $page = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('HenriqueKieckbusch_Jev::activity');
        $page->getConfig()->getTitle()->prepend(__('Jev Activity'));
        return $page;
    }
}
