<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_id',
        'type',
        'student_type',
        'target_program',
        'year_level',
        'semester',
        'academic_year',
        'educational_background',
        'previous_school',
        'contact_number',
        'address',
        'emergency_contact',
        'submitted_documents',
        'status',
        'evaluation_notes',
        'reviewed_by',
        'assigned_section',
    ];

    protected $casts = [
        'submitted_documents' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

