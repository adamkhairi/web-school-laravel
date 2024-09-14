<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            [
                'name' => 'Admin',
                'description' => 'Administrator with full access',
            ],
            [
                'name' => 'Teacher',
                'description' => 'Educator with access to classes and grades',
            ],
            [
                'name' => 'Student',
                'description' => 'Learner with access to courses and assignments',
            ],
            [
                'name' => 'Parent',
                'description' => 'Guardian with limited access to student information',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
