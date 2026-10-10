<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionEnrollment extends Model
{
    protected $fillable = ['academic_section_id', 'student_id', 'status', 'enrolled_at'];

    public function section()
    {
        return $this->belongsTo(AcademicSection::class, 'academic_section_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
