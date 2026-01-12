<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Enums\JournalStatus;
use App\Enums\JournalType;
use App\Exceptions\Accounting\ClosedPeriodException;
use App\Exceptions\Accounting\InactiveAccountException;
use App\Exceptions\Accounting\UnbalancedJournalException;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * JournalService
 * 
 * Handles journal entry creation, posting, and voiding.
 * Enforces double-entry accounting rules.
 */
class JournalService
{
    public function __construct(
        private TenantContext $tenantContext,
        private AccountBalanceService $balanceService
    ) {}

    /**
     * Create a new journal entry.
     */
    public function create(array $data): Journal
    {
        return DB::transaction(function () use ($data) {
            // Get or create period for transaction date
            $period = AccountingPeriod::getOrCreateForDate(
                $this->tenantContext->companyId(),
                \Carbon\Carbon::parse($data['transaction_date'])
            );

            // Generate reference number
            $reference = $this->generateReference(
                JournalType::from($data['type'] ?? 'general')
            );

            // Create journal
            $journal = Journal::create([
                'company_id' => $this->tenantContext->companyId(),
                'branch_id' => $this->tenantContext->branchId(),
                'period_id' => $period->id,
                'reference' => $reference,
                'type' => $data['type'] ?? JournalType::GENERAL,
                'transaction_date' => $data['transaction_date'],
                'status' => JournalStatus::DRAFT,
                'currency' => $data['currency'] ?? 'SAR',
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Add lines
            if (!empty($data['lines'])) {
                $lineNumber = 0;
                foreach ($data['lines'] as $lineData) {
                    $lineNumber++;
                    $journal->lines()->create([
                        'account_id' => $lineData['account_id'],
                        'cost_center_id' => $lineData['cost_center_id'] ?? null,
                        'debit' => $lineData['debit'] ?? 0,
                        'credit' => $lineData['credit'] ?? 0,
                        'description' => $lineData['description'] ?? null,
                        'line_number' => $lineNumber,
                        'reference_type' => $lineData['reference_type'] ?? null,
                        'reference_id' => $lineData['reference_id'] ?? null,
                    ]);
                }
            }

            // Recalculate totals
            $journal->recalculateTotals();

            return $journal->fresh(['lines']);
        });
    }

    /**
     * Post a journal entry (affects account balances).
     */
    public function post(Journal $journal): void
    {
        // Validate before posting
        $this->validateForPosting($journal);

        DB::transaction(function () use ($journal) {
            // Update journal status
            $journal->update([
                'status' => JournalStatus::POSTED,
                'posting_date' => now(),
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            // Update account balances
            foreach ($journal->lines as $line) {
                $this->balanceService->updateFromJournalLine($line, $journal);
            }
        });
    }

    /**
     * Void a posted journal (creates reversal entry).
     */
    public function void(Journal $journal, string $reason): Journal
    {
        if (!$journal->canVoid()) {
            throw new \InvalidArgumentException('Journal cannot be voided');
        }

        return DB::transaction(function () use ($journal, $reason) {
            // Create reversal journal
            $reversal = $this->createReversal($journal);

            // Post the reversal
            $this->post($reversal);

            // Update original journal
            $journal->update([
                'status' => JournalStatus::VOIDED,
                'reversed_by_id' => $reversal->id,
                'voided_by' => auth()->id(),
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            return $reversal;
        });
    }

    /**
     * Create a reversal journal entry.
     */
    private function createReversal(Journal $original): Journal
    {
        $reversal = Journal::create([
            'company_id' => $original->company_id,
            'branch_id' => $original->branch_id,
            'period_id' => $original->period_id,
            'reference' => $this->generateReference(JournalType::REVERSAL),
            'type' => JournalType::REVERSAL,
            'transaction_date' => now(),
            'status' => JournalStatus::DRAFT,
            'currency' => $original->currency,
            'exchange_rate' => $original->exchange_rate,
            'description' => "Reversal of {$original->reference}",
            'reversal_of_id' => $original->id,
            'created_by' => auth()->id(),
        ]);

        // Create reversed lines (swap debit/credit)
        foreach ($original->lines as $line) {
            $reversal->lines()->create([
                'account_id' => $line->account_id,
                'cost_center_id' => $line->cost_center_id,
                'debit' => $line->credit, // Swap
                'credit' => $line->debit, // Swap
                'description' => "Reversal: {$line->description}",
                'line_number' => $line->line_number,
            ]);
        }

        $reversal->recalculateTotals();

        return $reversal;
    }

    /**
     * Validate journal is ready for posting.
     */
    private function validateForPosting(Journal $journal): void
    {
        // Check if already posted
        if ($journal->status !== JournalStatus::DRAFT) {
            throw new \InvalidArgumentException('Only draft journals can be posted');
        }

        // Check balance
        if (!$journal->isBalanced()) {
            throw new UnbalancedJournalException(
                'Journal is not balanced. Debits must equal credits.'
            );
        }

        // Check period is open
        if (!$journal->period->allowsPosting()) {
            throw new ClosedPeriodException(
                "Period {$journal->period->name} is closed for posting."
            );
        }

        // Check all accounts are active and postable
        foreach ($journal->lines as $line) {
            $account = $line->account;
            if (!$account->canPost()) {
                throw new InactiveAccountException(
                    "Account {$account->code} does not allow posting."
                );
            }
        }
    }

    /**
     * Generate next reference number for journal type.
     */
    public function generateReference(JournalType $type): string
    {
        $prefix = $type->referencePrefix();
        $year = now()->format('Y');

        // Get the last reference for this type and year
        $lastJournal = Journal::where('company_id', $this->tenantContext->companyId())
            ->where('reference', 'like', "{$prefix}-{$year}-%")
            ->orderByRaw("CAST(SUBSTR(reference, -5) AS INTEGER) DESC")
            ->first();

        $nextNumber = 1;
        if ($lastJournal) {
            $parts = explode('-', $lastJournal->reference);
            $nextNumber = (int) end($parts) + 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $year, $nextNumber);
    }
}
