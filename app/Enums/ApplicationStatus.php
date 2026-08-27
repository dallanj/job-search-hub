<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Saved = 'saved';
    case Applied = 'applied';
    case Screening = 'screening';
    case Interview = 'interview';
    case Offer = 'offer';
    case Hired = 'hired';
    case Rejected = 'rejected';
    case NoResponse = 'no_response';
    case OfferDeclined = 'offer_declined';
    case Withdrawn = 'withdrawn';
    case Archived = 'archived';
}
