<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'description',
        'units',
        'department',
        'semester',
        'year_level',
        'schedule',
        'instructor',
    ];

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function sections()
    {
        return $this->hasMany(AcademicSection::class);
    }
}

