<?php
// ============================================================================
// FILE 2: UUID Trait
// Path: app/Models/Traits/UsesUuid.php
// ============================================================================

namespace App\Models\Traits;

use Illuminate\Support\Str;

trait UsesUuid
{
    /**
     * Boot the UUID trait for the model.
     */
    protected static function bootUsesUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
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
}