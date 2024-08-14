<?php

declare(strict_types=1);

namespace Vonage\Video;

use Vonage\Entity\Hydrator\ArrayHydrateInterface;

class CaptionOptions implements ArrayHydrateInterface
{
    protected ?string $languageCode = null;

    protected ?int $maxDuration = null;

    protected ?bool $partialCaptions = null;

    protected ?string $statusCallbackUrl = null;

    public function __construct($data = [])
    {
        $this->fromArray($data);
    }

    public function fromArray(array $data): self
    {
        if (isset($data['language_code'])) {
            $this->languageCode = $data['languageCode'];
        }

        if (isset($data['max_duration'])) {
            $this->maxDuration = $data['maxDuration'];
        }

        if (isset($data['partial_captions'])) {
            $this->partialCaptions = $data['partialCaptions'];
        }

        if (isset($data['status_callback_url'])) {
            $this->statusCallbackUrl = $data['statusCallbackUrl'];
        }

        return $this;
    }

    public function toArray(): array
    {
        // TODO: Implement toArray() method.
    }
}