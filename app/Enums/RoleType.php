<?php

namespace App\Enums;

enum RoleType: string
{
    case Admin = 'Admin';
    case Teacher = 'Teacher';
    case Student = 'Student';
    case Parent = 'Parent';
    case Guest = 'Guest';

    public static function values()
    {
        return array_column(self::cases(), 'value');
    }
}
