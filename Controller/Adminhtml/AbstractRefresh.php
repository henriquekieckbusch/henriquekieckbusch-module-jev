<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Controller\Adminhtml;

use HenriqueKieckbusch\Jev\Model\Analyzer;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;

/**
 * "Refresh Jev" link handler: forces a new analysis of one entity and returns to its page.
 */
abstract class AbstractRefresh extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'HenriqueKieckbusch_Jev::refresh';

    /**
     * @param Context $context
     * @param Analyzer $analyzer
     */
    public function __construct(
        Context $context,
        private readonly Analyzer $analyzer
    ) {
        parent::__construct($context);
    }

    /**
     * Jev entity type handled by this controller, e.g. "order".
     *
     * @return string
     */
    abstract protected function getType(): string;

    /**
     * Where to go after the refresh.
     *
     * @param int $entityId
     * @param Redirect $redirect
     * @return Redirect
     */
    abstract protected function redirectBack(int $entityId, Redirect $redirect): Redirect;

    /**
     * Force a new analysis of the requested entity and redirect back to its page.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $entityId = (int)$this->getRequest()->getParam('id');
        $redirect = $this->resultRedirectFactory->create();
        if ($entityId <= 0) {
            $this->messageManager->addErrorMessage(__('Missing entity id.'));
            return $redirect->setPath('adminhtml/dashboard');
        }
        try {
            $this->analyzer->analyze($this->getType(), $entityId, true);
            $this->messageManager->addSuccessMessage(__('Jev analysis refreshed.'));
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Jev could not refresh: %1', $e->getMessage()));
        }
        return $this->redirectBack($entityId, $redirect);
    }
}
