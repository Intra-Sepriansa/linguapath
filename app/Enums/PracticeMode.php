<?php

namespace App\Enums;

enum PracticeMode: string
{
    case Quick = 'quick';
    case Focus = 'focus';
    case Weakness = 'weakness';
    case Review = 'review';
    case Lesson = 'lesson';
}
