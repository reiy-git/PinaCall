<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProvisioningEmail extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['temporary_password'];

    protected function casts(): array
    {
        return ['temporary_password' => 'encrypted', 'available_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
