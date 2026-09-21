<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Source;

use HenriqueKieckbusch\Jev\Model\Handler\Pool;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Analyzable entity types as grid filter options.
 */
class EntityType implements OptionSourceInterface
{
    /**
     * @param Pool $pool
     */
    public function __construct(
        private readonly Pool $pool
    ) {
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->pool->getAll() as $type => $handler) {
            $options[] = ['value' => $type, 'label' => __($handler->getLabel())];
        }
        return $options;
    }
}
