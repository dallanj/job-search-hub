<?php

namespace App\Enums;

enum InterviewType: string
{
    case Phone = 'phone';
    case Video = 'video';
    case Onsite = 'onsite';
    case Technical = 'technical';
    case Behavioral = 'behavioral';
    case Panel = 'panel';
    case Final = 'final';
}
