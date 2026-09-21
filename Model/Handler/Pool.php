<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model\Handler;

use Magento\Framework\Exception\LocalizedException;

/**
 * Registry of entity handlers, configured in di.xml.
 */
class Pool
{
    /**
     * @var HandlerInterface[]
     */
    private array $handlers = [];

    /**
     * @param HandlerInterface[] $handlers
     * @throws LocalizedException
     */
    public function __construct(array $handlers = [])
    {
        foreach ($handlers as $handler) {
            if (!$handler instanceof HandlerInterface) {
                throw new LocalizedException(__('Jev handlers must implement %1.', HandlerInterface::class));
            }
            $this->handlers[$handler->getType()] = $handler;
        }
    }

    /**
     * Get the handler registered for the given entity type.
     *
     * @param string $type
     * @return HandlerInterface
     * @throws LocalizedException
     */
    public function get(string $type): HandlerInterface
    {
        if (!isset($this->handlers[$type])) {
            throw new LocalizedException(__('Unknown Jev entity type "%1".', $type));
        }
        return $this->handlers[$type];
    }

    /**
     * Whether a handler is registered for the given entity type.
     *
     * @param string $type
     * @return bool
     */
    public function has(string $type): bool
    {
        return isset($this->handlers[$type]);
    }

    /**
     * Get every registered handler.
     *
     * @return HandlerInterface[] Keyed by type
     */
    public function getAll(): array
    {
        return $this->handlers;
    }
}
