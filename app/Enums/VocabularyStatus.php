<?php

namespace App\Enums;

enum VocabularyStatus: string
{
    case Learning = 'learning';
    case Mastered = 'mastered';
    case ReviewLater = 'review_later';
    case Weak = 'weak';
}
