<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_id',
        'academic_section_id',
        'academic_year',
        'semester',
        'midterm_grade',
        'final_grade',
        'grade',
        'status',
        'grades_released',
        'remarks',
    ];

    protected function casts(): array
    {
        return ['grades_released' => 'boolean'];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function section()
    {
        return $this->belongsTo(AcademicSection::class, 'academic_section_id');
    }
}
