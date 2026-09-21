<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Controller\Adminhtml;

use HenriqueKieckbusch\Jev\Exception\DisabledException;
use HenriqueKieckbusch\Jev\Model\Analyzer;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Ui\Component\MassAction\Filter;

/**
 * "Refresh Jev" mass action: forces a new analysis of every selected grid row.
 */
abstract class AbstractMass extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'HenriqueKieckbusch_Jev::refresh';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param Analyzer $analyzer
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly Analyzer $analyzer
    ) {
        parent::__construct($context);
    }

    /**
     * Jev entity type, e.g. "order".
     *
     * @return string
     */
    abstract protected function getType(): string;

    /**
     * Fresh collection of the grid's entity type.
     *
     * @return AbstractDb
     */
    abstract protected function createCollection(): AbstractDb;

    /**
     * Grid to return to.
     *
     * @return string
     */
    abstract protected function getGridPath(): string;

    /**
     * Analyze every selected grid row and redirect back to the grid.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $redirect = $this->resultRedirectFactory->create();
        try {
            $ids = $this->filter->getCollection($this->createCollection())->getAllIds();
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirect->setPath($this->getGridPath());
        }
        $this->analyzeAll(array_map('intval', $ids), $redirect);
        return $redirect->setPath($this->getGridPath());
    }

    /**
     * Analyze each id, collect success/failure counts, and report them to the admin.
     *
     * @param int[] $ids
     * @param Redirect $redirect
     * @return void
     */
    private function analyzeAll(array $ids, Redirect $redirect): void
    {
        $done = 0;
        $failed = 0;
        foreach ($ids as $id) {
            try {
                $this->analyzer->analyze($this->getType(), $id, true);
                $done++;
            } catch (DisabledException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return;
            } catch (\Throwable $e) {
                $failed++;
                $this->messageManager->addErrorMessage(__('#%1: %2', $id, $e->getMessage()));
            }
        }
        if ($done > 0) {
            $this->messageManager->addSuccessMessage(__('Jev analyzed %1 record(s).', $done));
        }
        if ($failed > 0) {
            $this->messageManager->addWarningMessage(__('Jev could not analyze %1 record(s).', $failed));
        }
        $redirect->setPath($this->getGridPath());
    }
}
