<?php

namespace App\Enums;

enum SkillType: string
{
    case Listening = 'listening';
    case Structure = 'structure';
    case Reading = 'reading';
    case Vocabulary = 'vocabulary';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Listening => 'Listening',
            self::Structure => 'Structure',
            self::Reading => 'Reading',
            self::Vocabulary => 'Vocabulary',
            self::Mixed => 'Mixed',
        };
    }
}
