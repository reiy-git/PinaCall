<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department',
        'is_active',
        'must_change_password',
        'temporary_password_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'temporary_password_expires_at' => 'datetime',
        ];
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function applications()
    {
        return $this->hasMany(StudentApplication::class);
    }

    public function assignedSections()
    {
        return $this->hasMany(AcademicSection::class, 'faculty_id');
    }

    public function apiAccessTokens()
    {
        return $this->hasMany(ApiAccessToken::class);
    }
}
