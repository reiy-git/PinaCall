<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhysicalQueueTokenEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'physical_queue_token_id',
        'student_id',
        'queue_token_id',
        'staff_id',
        'event_type',
        'notes',
    ];

    public function physicalQueueToken()
    {
        return $this->belongsTo(PhysicalQueueToken::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function queueTicket()
    {
        return $this->belongsTo(QueueToken::class, 'queue_token_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
