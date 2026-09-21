<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Controller\Adminhtml\Mass;

use HenriqueKieckbusch\Jev\Controller\Adminhtml\AbstractMass;
use HenriqueKieckbusch\Jev\Model\Analyzer;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Products grid mass action "Refresh Jev".
 */
class Product extends AbstractMass
{
    /**
     * @param Context $context
     * @param Filter $filter
     * @param Analyzer $analyzer
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        Analyzer $analyzer,
        private readonly CollectionFactory $collectionFactory
    ) {
        parent::__construct($context, $filter, $analyzer);
    }

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
    protected function createCollection(): AbstractDb
    {
        return $this->collectionFactory->create();
    }

    /**
     * @inheritdoc
     */
    protected function getGridPath(): string
    {
        return 'catalog/product/index';
    }
}
