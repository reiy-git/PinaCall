<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhysicalQueueToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'qr_secret_hash',
        'qr_secret_encrypted',
        'service_stage',
        'status',
        'assigned_student_id',
        'assigned_queue_token_id',
        'last_reset_by_staff_id',
        'assigned_at',
        'last_reset_at',
    ];

    protected $hidden = [
        'qr_secret_hash',
        'qr_secret_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'qr_secret_encrypted' => 'encrypted',
            'assigned_at' => 'datetime',
            'last_reset_at' => 'datetime',
        ];
    }

    public function assignedStudent()
    {
        return $this->belongsTo(Student::class, 'assigned_student_id');
    }

    public function assignedQueueTicket()
    {
        return $this->belongsTo(QueueToken::class, 'assigned_queue_token_id');
    }

    public function queueTicket()
    {
        return $this->assignedQueueTicket();
    }

    public function lastResetByStaff()
    {
        return $this->belongsTo(User::class, 'last_reset_by_staff_id');
    }

    public function attempts()
    {
        return $this->hasMany(PhysicalQueueTokenAttempt::class);
    }

    public function events()
    {
        return $this->hasMany(PhysicalQueueTokenEvent::class);
    }
}
