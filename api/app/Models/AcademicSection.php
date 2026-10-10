<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSection extends Model
{
    protected $fillable = [
        'course_id', 'faculty_id', 'section_code', 'program', 'year_level',
        'academic_year', 'semester', 'schedule', 'room', 'capacity', 'status',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function roster()
    {
        return $this->hasMany(SectionEnrollment::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'section_enrollments')
            ->wherePivot('status', 'enrolled')
            ->withPivot(['status', 'enrolled_at'])
            ->withTimestamps();
    }
}
