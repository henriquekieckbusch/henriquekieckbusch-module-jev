<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Setup\Patch\Data;

use HenriqueKieckbusch\Jev\Model\Question\CustomerQuestions;
use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Creates one "select" customer attribute per Jev customer question, hidden from the
 * standard customer form (they are shown only in the "Jev" tab) and visible in the grid.
 */
class AddCustomerAttributes implements DataPatchInterface
{
    private const GROUP = 'Jev';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param CustomerQuestions $questions
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly CustomerSetupFactory $customerSetupFactory,
        private readonly CustomerQuestions $questions
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var \Magento\Customer\Setup\CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $setId = $customerSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $customerSetup->addAttributeGroup(Customer::ENTITY, $setId, self::GROUP, 100);
        $groupId = $customerSetup->getAttributeGroupId(Customer::ENTITY, $setId, self::GROUP);

        $sortOrder = 10;
        foreach ($this->questions->getQuestions() as $question) {
            $code = $question->getAttributeCode();
            $customerSetup->addAttribute(Customer::ENTITY, $code, [
                'type' => 'varchar',
                'label' => $question->getLabel(),
                'input' => 'select',
                'source' => \HenriqueKieckbusch\Jev\Model\Source\CustomerOptions::class,
                'required' => false,
                'visible' => false,
                'user_defined' => false,
                'system' => 0,
                'position' => $sortOrder,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => false,
            ]);
            $attributeId = (int)$customerSetup->getAttributeId(Customer::ENTITY, $code);
            $customerSetup->addAttributeToSet(Customer::ENTITY, $setId, $groupId, $attributeId, $sortOrder);
            $sortOrder += 10;
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
