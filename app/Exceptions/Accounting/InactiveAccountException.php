<?php

declare(strict_types=1);

namespace App\Exceptions\Accounting;

use Exception;

/**
 * InactiveAccountException
 * 
 * Thrown when attempting to post to an inactive or non-postable account.
 */
class InactiveAccountException extends Exception
{
    public function __construct(string $message = 'Account does not allow posting')
    {
        parent::__construct($message);
    }
}
