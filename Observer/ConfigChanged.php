<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Observer;

use HenriqueKieckbusch\Jev\Model\Backfill;
use HenriqueKieckbusch\Jev\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

/**
 * After the Jev configuration section is saved with a new API key and Jev enabled,
 * analyze the latest orders and customers that have no analysis yet.
 */
class ConfigChanged implements ObserverInterface
{
    /**
     * @param Config $config
     * @param Backfill $backfill
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        private readonly Config $config,
        private readonly Backfill $backfill,
        private readonly ManagerInterface $messageManager
    ) {
    }

    /**
     * Backfill analysis for existing orders and customers once Jev becomes ready.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $changedPaths = (array)$observer->getEvent()->getData('changed_paths');
        if (!in_array(Config::XML_PATH_API_KEY, $changedPaths, true) || !$this->config->isReady()) {
            return;
        }
        $counts = $this->backfill->run();
        $this->messageManager->addSuccessMessage(
            __('Jev analyzed %1 order(s) and %2 customer(s).', $counts['order'], $counts['customer'])
        );
    }
}
