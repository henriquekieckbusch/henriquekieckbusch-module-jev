<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Block\Adminhtml\Review;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Wraps the shared Jev Panel block below the review edit form.
 */
class AnalysisPanel extends Template
{
    /**
     * @var string
     */
    protected $_template = 'HenriqueKieckbusch_Jev::review/analysis.phtml';

    /**
     * Id of the review being edited.
     *
     * @return int
     */
    public function getReviewId(): int
    {
        return (int)$this->getRequest()->getParam('id');
    }
}
