<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case RetestReady = 'retest_ready';
    case Resolved = 'resolved';
    case Fixed = 'fixed';
}
