<?php
declare(strict_types=1);

namespace Vonage\Video\Archive;

class Transcription
{
    public readonly TranscriptionStatus $status;
    public readonly ?string $url;
    public readonly ?string $reason;
    public readonly string $primaryLanguageCode;
    public readonly bool $hasSummary;

    public function __construct(array $data)
    {
        $this->status = TranscriptionStatus::from($data['status']);
        $this->url = $data['url'] ?? null;
        $this->reason = $data['reason'] ?? null;
        $this->primaryLanguageCode = $data['primaryLanguageCode'] ?? 'en-US';
        $this->hasSummary = $data['hasSummary'] ?? false;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'url' => $this->url,
            'reason' => $this->reason,
            'primaryLanguageCode' => $this->primaryLanguageCode,
            'hasSummary' => $this->hasSummary,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @deprecated Use status property directly
     */
    public function getStatus(): TranscriptionStatus
    {
        return $this->status;
    }

    /**
     * @deprecated Use url property directly
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @deprecated Use reason property directly
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * @deprecated Use primaryLanguageCode property directly
     */
    public function getPrimaryLanguageCode(): string
    {
        return $this->primaryLanguageCode;
    }

    /**
     * @deprecated Use hasSummary property directly
     */
    public function getHasSummary(): bool
    {
        return $this->hasSummary;
    }
}