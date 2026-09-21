<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\ResourceModel\Analysis;

use HenriqueKieckbusch\Jev\Model\Analysis;
use HenriqueKieckbusch\Jev\Model\ResourceModel\Analysis as AnalysisResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Collection of Jev analyses.
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'analysis_id';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(Analysis::class, AnalysisResource::class);
    }
}
