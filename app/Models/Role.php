<?php

namespace App\Models;

use App\Enums\RoleType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;//, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['name', 'description'];
    protected $casts = [
        'name' => RoleType::class,
    ];

    public static function create(array $validatedData)
    {
        return static::query()->create($validatedData);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
