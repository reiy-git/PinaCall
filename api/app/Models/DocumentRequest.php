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
}

