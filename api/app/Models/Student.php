<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'student_number',
        'course_program',
        'year_level',
        'student_type',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function applications()
    {
        return $this->hasMany(StudentApplication::class);
    }

    public function clearance()
    {
        return $this->hasOne(Clearance::class);
    }

    public function documentRequests()
    {
        return $this->hasMany(DocumentRequest::class);
    }

    public function sections()
    {
        return $this->belongsToMany(AcademicSection::class, 'section_enrollments')
            ->withPivot(['status', 'enrolled_at'])
            ->withTimestamps();
    }
}
