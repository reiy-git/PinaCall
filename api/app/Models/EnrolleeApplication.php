<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrolleeApplication extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['documents'];

    protected function casts(): array
    {
        return ['documents' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
