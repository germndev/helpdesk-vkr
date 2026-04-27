<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassificationRule extends Model
{
    protected $fillable = [
        'name',
        'type',
        'target_value',
        'assigned_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function triggers(): HasMany
    {
        return $this->hasMany(ClassificationRuleTrigger::class);
    }

    public function responsibles(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'classification_rule_user',
            'classification_rule_id',
            'user_id',
        )->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
