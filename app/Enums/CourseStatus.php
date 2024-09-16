<?php

namespace App\Enums;

enum CourseStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Completed = 'completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
