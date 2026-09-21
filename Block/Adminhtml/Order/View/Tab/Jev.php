<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Block\Adminhtml\Order\View\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Registry;

/**
 * "Jev" tab on the order view page. Inline (not ajax): rendered with the rest of the page.
 */
class Jev extends Template implements TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'HenriqueKieckbusch_Jev::order/tab.phtml';

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
     * Id of the order being viewed.
     *
     * @return int
     */
    public function getOrderId(): int
    {
        $order = $this->registry->registry('current_order');
        return $order ? (int)$order->getId() : (int)$this->getRequest()->getParam('order_id');
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
        return __('Jev Risk Assessment');
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return false;
    }
}
