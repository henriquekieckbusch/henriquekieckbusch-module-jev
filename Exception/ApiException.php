<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Thrown when the Typesafe API cannot be reached or answers with an error.
 */
class ApiException extends LocalizedException
{
}
