<?php

namespace App\Enums;

enum LessonStatus: string
{
  case Completed = 'completed';
  case InComplete = 'incomplete';

  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }
}
