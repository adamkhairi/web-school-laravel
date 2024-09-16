<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    public function run()
    {
        $courses = Course::all();

        foreach ($courses as $course) {
            $lessonCount = fake()->numberBetween(5, 15);

            for ($i = 1; $i <= $lessonCount; $i++) {
                Lesson::create([
                    'course_id' => $course->id,
                    'title' => "Lesson $i: " . fake()->sentence(),
                    'description' => fake()->paragraph(),
                    'content' => fake()->paragraphs(3, true),
                    'order' => $i,
                ]);
            }
        }
    }
}
