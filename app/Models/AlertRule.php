<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AlertType;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertRule extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'conditions',
        'email_enabled',
        'database_enabled',
        'recipients',
        'threshold',
        'days_before',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => AlertType::class,
            'conditions' => 'array',
            'recipients' => 'array',
            'email_enabled' => 'boolean',
            'database_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AlertLog::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
