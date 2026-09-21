<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Thrown when an analysis is requested while Jev is disabled or has no API key.
 */
class DisabledException extends LocalizedException
{
}
