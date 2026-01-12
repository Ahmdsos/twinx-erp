<?php

declare(strict_types=1);

namespace App\Exceptions\Accounting;

use Exception;

/**
 * UnbalancedJournalException
 * 
 * Thrown when attempting to post an unbalanced journal entry.
 */
class UnbalancedJournalException extends Exception
{
    public function __construct(string $message = 'Journal debits must equal credits')
    {
        parent::__construct($message);
    }
}
