<?php
declare(strict_types=1);

namespace Vonage\Video\Archive;

class TranscriptionOptions
{
    public readonly string $primaryLanguageCode;

    public readonly bool $hasSummary;

    public function __construct(?string $primaryLanguageCode = 'en-US', ?bool $hasSummary = false)
    {
        $this->primaryLanguageCode = $primaryLanguageCode;
        $this->hasSummary = $hasSummary;
    }
}