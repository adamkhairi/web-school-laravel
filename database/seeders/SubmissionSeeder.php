<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;

class SubmissionSeeder extends Seeder
{
    public function run()
    {
        $assignments = Assignment::all();
        $students = User::whereHas('roles', function ($query) {
            $query->where('name', 'Student');
        })->get();

        foreach ($assignments as $assignment) {
            $submissionCount = fake()->numberBetween(5, 15);

            for ($i = 1; $i <= $submissionCount; $i++) {
                Submission::create([
                    'assignment_id' => $assignment->id,
                    'student_id' => $students->random()->id,
                    'file_path' => 'submissions/' . fake()->uuid() . '.pdf',
                    'grade' => fake()->optional(0.8)->numberBetween(0, 100),
                ]);
            }
        }
    }
}
