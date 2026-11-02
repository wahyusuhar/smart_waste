<?php

namespace App\Enums;

enum ReportCategory: string
{
    case Organic = 'organic';
    case Plastic = 'plastic';
    case Paper = 'paper';

    public function label(): string
    {
        return match ($this) {
            self::Organic => 'Organik',
            self::Plastic => 'Plastik',
            self::Paper => 'Kertas',
        };
    }
}
