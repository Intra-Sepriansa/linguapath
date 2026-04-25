<?php

namespace App\Enums;

enum MistakeType: string
{
    case Grammar = 'grammar';
    case Vocabulary = 'vocabulary';
    case Listening = 'listening';
    case Reading = 'reading';
    case Time = 'time';
}
