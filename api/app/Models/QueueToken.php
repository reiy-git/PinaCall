<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueueToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_number',
        'daily_sequence',
        'student_id',
        'document_request_id',
        'service_stage',
        'window_number',
        'status',
        'queue_date',
        'checked_in_at',
        'called_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'queue_date' => 'date',
            'checked_in_at' => 'datetime',
            'called_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function documentRequest()
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    public static function nextSequence(string $stage = 'cashier'): int
    {
        $today = now()->toDateString();
        $max = static::where('queue_date', $today)
            ->where('service_stage', $stage)
            ->max('daily_sequence');

        return ($max ?? 0) + 1;
    }

    public static function generateTokenNumber(string $stage, int $sequence): string
    {
        $prefix = $stage === 'cashier' ? 'Q' : 'R';
        return sprintf('%s-%03d', $prefix, $sequence);
    }
}
