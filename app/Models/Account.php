<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountType;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

/**
 * Account Model
 * 
 * Represents an account in the Chart of Accounts with hierarchical structure.
 * Uses Adjacency List pattern (parent_id) for tree structure.
 * 
 * @property string $id
 * @property string $company_id
 * @property string|null $parent_id
 * @property string $code
 * @property string $name
 * @property string|null $name_ar
 * @property AccountType $type
 * @property bool $is_group
 * @property bool $is_system
 * @property int $level
 * @property string $normal_balance
 * @property bool $is_active
 * @property bool $allow_direct_posting
 */
class Account extends Model implements Auditable
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;
    use AuditableTrait;

    // Note: Not using BelongsToTenant as accounts are company-wide
    protected array $tenantColumns = ['company_id'];

    protected $fillable = [
        'company_id',
        'parent_id',
        'code',
        'name',
        'name_ar',
        'type',
        'is_group',
        'is_system',
        'level',
        'normal_balance',
        'is_active',
        'allow_direct_posting',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'is_group' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'allow_direct_posting' => 'boolean',
            'metadata' => 'array',
            'level' => 'integer',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id')->orderBy('code');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(AccountBalance::class);
    }

    // =========================================================================
    // TREE METHODS
    // =========================================================================

    /**
     * Get all ancestors (parent chain up to root).
     */
    public function ancestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors->reverse();
    }

    /**
     * Get all descendants (recursive children).
     */
    public function descendants(): Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }

        return $descendants;
    }

    /**
     * Get the full path (code trail).
     */
    public function getFullPathAttribute(): string
    {
        $path = $this->ancestors()->pluck('code')->push($this->code);
        return $path->implode(' > ');
    }

    /**
     * Check if this account is an ancestor of another.
     */
    public function isAncestorOf(Account $account): bool
    {
        return $account->ancestors()->contains('id', $this->id);
    }

    // =========================================================================
    // DISPLAY METHODS
    // =========================================================================

    /**
     * Get display name based on locale.
     */
    public function getDisplayNameAttribute(): string
    {
        if (app()->getLocale() === 'ar' && $this->name_ar) {
            return $this->name_ar;
        }
        return $this->name;
    }

    /**
     * Get formatted code with name.
     */
    public function getCodeNameAttribute(): string
    {
        return "{$this->code} - {$this->display_name}";
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    /**
     * Check if posting is allowed to this account.
     */
    public function canPost(): bool
    {
        return $this->is_active 
            && $this->allow_direct_posting 
            && !$this->is_group;
    }

    /**
     * Calculate balance at a specific date.
     */
    public function balanceAtDate(\Carbon\Carbon $date): array
    {
        $balance = $this->journalLines()
            ->whereHas('journal', function ($query) use ($date) {
                $query->where('status', 'posted')
                    ->where('transaction_date', '<=', $date);
            })
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        return [
            'debit' => (float) ($balance->total_debit ?? 0),
            'credit' => (float) ($balance->total_credit ?? 0),
            'balance' => $this->calculateNetBalance(
                (float) ($balance->total_debit ?? 0),
                (float) ($balance->total_credit ?? 0)
            ),
        ];
    }

    /**
     * Calculate net balance based on normal balance type.
     */
    public function calculateNetBalance(float $debit, float $credit): float
    {
        if ($this->normal_balance === 'debit') {
            return $debit - $credit;
        }
        return $credit - $debit;
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePostable($query)
    {
        return $query->where('is_active', true)
            ->where('allow_direct_posting', true)
            ->where('is_group', false);
    }

    public function scopeOfType($query, AccountType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRootLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
