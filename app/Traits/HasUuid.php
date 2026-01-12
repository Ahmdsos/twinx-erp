<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * Trait HasUuid
 * 
 * Automatically generates UUID for models using this trait.
 * Ensures all primary keys are UUIDs as per TWINX ERP standards.
 */
trait HasUuid
{
    /**
     * Boot the trait
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            $key = $model->getKeyName();
            if (!isset($model->{$key}) || $model->{$key} === null || $model->{$key} === '') {
                $model->{$key} = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     */
    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * Generate a new UUID
     */
    public static function generateUuid(): string
    {
        return (string) Str::uuid();
    }
}
