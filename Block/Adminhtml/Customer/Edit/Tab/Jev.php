<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Block\Adminhtml\Customer\Edit\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Registry;
use Magento\Ui\Component\Layout\Tabs\TabInterface;

/**
 * "Jev" tab on the customer edit page.
 */
class Jev extends Template implements TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'HenriqueKieckbusch_Jev::customer/tab/jev.phtml';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Id of the customer being edited.
     *
     * @return int
     */
    public function getCustomerId(): int
    {
        $id = $this->registry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
        return $id ? (int)$id : (int)$this->getRequest()->getParam('id');
    }

    /**
     * @inheritdoc
     */
    public function getTabLabel()
    {
        return __('Jev');
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle()
    {
        return __('Jev Customer Assessment');
    }

    /**
     * @inheritdoc
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return (bool)$this->getCustomerId();
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return !$this->canShowTab();
    }
}
