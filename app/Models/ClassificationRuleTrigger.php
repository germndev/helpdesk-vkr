<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassificationRuleTrigger extends Model
{
    protected $fillable = [
        'classification_rule_id',
        'phrase',
        'weight',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ClassificationRule::class, 'classification_rule_id');
    }
}
