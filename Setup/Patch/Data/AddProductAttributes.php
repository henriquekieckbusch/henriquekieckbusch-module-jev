<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Setup\Patch\Data;

use HenriqueKieckbusch\Jev\Model\Question\ProductQuestions;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Creates one "select" product attribute per Jev product question, grouped under a
 * dedicated "Jev" attribute group on every attribute set, read-only and updated on save.
 */
class AddProductAttributes implements DataPatchInterface
{
    public const GROUP = 'Jev';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param ProductQuestions $questions
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory,
        private readonly ProductQuestions $questions
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $sortOrder = 10;
        foreach ($this->questions->getQuestions() as $question) {
            $eavSetup->addAttribute(Product::ENTITY, $question->getAttributeCode(), [
                'type' => 'varchar',
                'label' => $question->getLabel(),
                'input' => 'select',
                'source' => \HenriqueKieckbusch\Jev\Model\Source\ProductOptions::class,
                'required' => false,
                'visible' => true,
                'user_defined' => true,
                'system' => 0,
                'group' => self::GROUP,
                'sort_order' => $sortOrder,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => true,
            ]);
            $sortOrder += 10;
        }

        foreach ($eavSetup->getAllAttributeSetIds(Product::ENTITY) as $setId) {
            $eavSetup->addAttributeGroup(Product::ENTITY, $setId, self::GROUP, 100);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
