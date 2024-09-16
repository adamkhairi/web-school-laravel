<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use App\Enums\CourseStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition()
    {
        return [
            'name' => 'Course in ' . $this->faker->word(),
            'description' => $this->faker->paragraph(),
            'teacher_id' => User::factory(),
            'start_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'end_date' => $this->faker->dateTimeBetween('+2 months', '+6 months'),
            'status' => $this->faker->randomElement(CourseStatus::cases()),
            'capacity' => $this->faker->numberBetween(10, 50),
        ];
    }
}
