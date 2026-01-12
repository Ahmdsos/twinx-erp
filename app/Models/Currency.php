<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Currency extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'name_ar',
        'symbol',
        'decimal_places',
        'is_base',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_base' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'ar' && $this->name_ar 
            ? $this->name_ar 
            : $this->name;
    }
}
