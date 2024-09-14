<?php

namespace Database\Seeders;

use App\Enums\RoleType;
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
                'name' => RoleType::Admin->value,
                'description' => 'Administrator with full access',
            ],
            [
                'name' => RoleType::Teacher->value,
                'description' => 'Educator with access to classes and grades',
            ],
            [
                'name' => RoleType::Student->value,
                'description' => 'Learner with access to courses and assignments',
            ],
            [
                'name' => RoleType::Parent->value,
                'description' => 'Guardian with limited access to student information',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
