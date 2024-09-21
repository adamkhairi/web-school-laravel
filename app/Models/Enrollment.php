<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;


    protected $fillable = ['user_id', 'course_id', 'status'];

    protected $casts = [
        'status' => EnrollmentStatus::class,
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', EnrollmentStatus::Approved->value);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
