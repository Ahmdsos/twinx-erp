<?php

declare(strict_types=1);

namespace App\Exceptions\Accounting;

use Exception;

/**
 * ClosedPeriodException
 * 
 * Thrown when attempting to post to a closed accounting period.
 */
class ClosedPeriodException extends Exception
{
    public function __construct(string $message = 'Cannot post to a closed period')
    {
        parent::__construct($message);
    }
}
