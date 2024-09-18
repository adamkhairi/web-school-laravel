<?php

namespace App\Models;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'teacher_id',
        'start_date',
        'end_date',
        'status',
        'capacity',
        'access_code',
    ];

    protected $casts = [
        'status' => CourseStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
    ];
    
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

   public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->wherePivot('status', EnrollmentStatus::Approved);
    }

    public function materials()
    {
        return $this->hasMany(CourseMaterial::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function progressPercentage(User $user)
    {
        $totalLessons = $this->lessons()->count();
        $completedLessons = $this->lessons()
            ->whereHas('progress', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('completed', true);
            })
            ->count();
    
        return $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;
    }
}
