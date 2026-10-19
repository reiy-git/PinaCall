<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'type',
        'document_type',
        'purpose',
        'copies',
        'fee',
        'payment_status',
        'status',
        'remarks',
        'released_at',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function queueTokens()
    {
        return $this->hasMany(QueueToken::class);
    }

    public function activeQueueToken()
    {
        return $this->hasOne(QueueToken::class)
            ->whereDate('queue_date', now()->toDateString())
            ->whereNotIn('status', ['completed', 'no_show', 'skipped'])
            ->latest();
    }
}

