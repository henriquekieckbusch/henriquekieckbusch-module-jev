<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Clears the static "note" a previous version of AddProductAttributes wrote on every
 * Jev product attribute. The note is now generated dynamically per product by
 * Ui\DataProvider\Product\Form\Modifier\AnswerNotice, so a static one left over from
 * an earlier install would otherwise show even when the current answer has no
 * longer description of its own.
 */
class ClearProductAttributeNotes implements DataPatchInterface
{
    private const STALE_NOTE = 'Computed automatically by Jev when the product is saved.';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $connection = $this->moduleDataSetup->getConnection();
        $connection->update(
            $this->moduleDataSetup->getTable('eav_attribute'),
            ['note' => null],
            [
                'attribute_code LIKE ?' => 'jev\\_%',
                'note = ?' => self::STALE_NOTE,
            ]
        );
        $this->moduleDataSetup->getConnection()->endSetup();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [AddProductAttributes::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
