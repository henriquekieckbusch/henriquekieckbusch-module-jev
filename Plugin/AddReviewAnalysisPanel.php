<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Plugin;

use HenriqueKieckbusch\Jev\Block\Adminhtml\Review\AnalysisPanel;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Review\Block\Adminhtml\Edit;

/**
 * Renders the Jev panel right below the review edit form. review_product_edit.xml does not
 * exist in this codebase (the page is built programmatically), so a plugin is the only hook.
 */
class AddReviewAnalysisPanel
{
    /**
     * Attach the Jev analysis panel below the review edit form.
     *
     * @param Edit $subject
     * @param AbstractBlock $result
     * @return AbstractBlock
     */
    public function afterSetLayout(Edit $subject, AbstractBlock $result): AbstractBlock
    {
        $form = $subject->getChildBlock('form');
        if ($form instanceof AbstractBlock && !$form->getChildBlock('form_after')) {
            $form->setChild(
                'form_after',
                $subject->getLayout()->createBlock(AnalysisPanel::class, 'jev.review.analysis')
            );
        }
        return $result;
    }
}
