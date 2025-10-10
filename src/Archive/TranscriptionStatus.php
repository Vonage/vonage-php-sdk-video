<?php
declare(strict_types=1);

namespace Vonage\Video\Archive;

enum TranscriptionStatus: string
{
    case requested = 'requested';
    case available = 'available';
    case failed = 'failed';
    case started = 'started';
    case uploaded = 'uploaded';
}