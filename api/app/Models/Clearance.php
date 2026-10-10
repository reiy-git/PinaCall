<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clearance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'academic_year',
        'semester',
        'cashier_status',
        'cashier_remarks',
        'library_status',
        'library_remarks',
        'dept_status',
        'dept_remarks',
        'dean_status',
        'dean_remarks',
        'guidance_status',
        'guidance_remarks',
        'registrar_status',
        'registrar_remarks',
        'status',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

