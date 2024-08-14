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
        if (isset($data['languageCode'])) {
            $this->languageCode = $data['languageCode'];
        }

        if (isset($data['maxDuration'])) {
            $this->maxDuration = $data['maxDuration'];
        }

        if (isset($data['partialCaptions'])) {
            $this->partialCaptions = $data['partialCaptions'];
        }

        if (isset($data['statusCallbackUrl'])) {
            $this->statusCallbackUrl = $data['statusCallbackUrl'];
        }

        return $this;
    }

    public function toArray(): array
    {
        $return = [];

        if (!is_null($this->getLanguageCode())) {
            $return['languageCode'] = $this->getLanguageCode();
        }

        if (!is_null($this->getMaxDuration())) {
            $return['maxDuration'] = $this->getMaxDuration();
        }

        if (!is_null($this->getPartialCaptions())) {
            $return['partialCaptions'] = $this->getPartialCaptions();
        }

        if (!is_null($this->getStatusCallbackUrl())) {
            $return['statusCallbackUrl'] = $this->getStatusCallbackUrl();
        }

        return $return;
    }

    public function getLanguageCode(): ?string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(?string $languageCode): self
    {
        $this->languageCode = $languageCode;
        return $this;
    }

    public function getMaxDuration(): ?int
    {
        return $this->maxDuration;
    }

    public function setMaxDuration(?int $maxDuration): self
    {
        $this->maxDuration = $maxDuration;
        return $this;
    }

    public function getPartialCaptions(): ?bool
    {
        return $this->partialCaptions;
    }

    public function setPartialCaptions(?bool $partialCaptions): self
    {
        $this->partialCaptions = $partialCaptions;
        return $this;
    }

    public function getStatusCallbackUrl(): ?string
    {
        return $this->statusCallbackUrl;
    }

    public function setStatusCallbackUrl(?string $statusCallbackUrl): self
    {
        $this->statusCallbackUrl = $statusCallbackUrl;
        return $this;
    }
}