<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Database\Seeder;

class AssignmentSeeder extends Seeder
{
    public function run()
    {
        $courses = Course::all();

        foreach ($courses as $course) {
            $assignmentCount = fake()->numberBetween(3, 8);

            for ($i = 1; $i <= $assignmentCount; $i++) {
                Assignment::create([
                    'course_id' => $course->id,
                    'title' => "Assignment $i: " . fake()->sentence(),
                    'description' => fake()->paragraph(),
                    'due_date' => fake()->dateTimeBetween($course->start_date, $course->end_date),
                ]);
            }
        }
    }
}
