<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Saved = 'saved';
    case Applied = 'applied';
    case Screening = 'screening';
    case Interview = 'interview';
    case Offer = 'offer';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case Archived = 'archived';
}
