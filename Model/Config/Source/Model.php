<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Models offered by the Typesafe API.
 */
class Model implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'jev-latest', 'label' => __('jev-latest (stable)')],
            ['value' => 'jev-preview', 'label' => __('jev-preview (next version)')],
        ];
    }
}
