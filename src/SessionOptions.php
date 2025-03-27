<?php

declare(strict_types=1);

namespace Vonage\Video;

use Vonage\Video\Archive\ArchiveMode;

class SessionOptions
{
    /**
     * @var string
     */
    protected $archiveMode;

    /**
     * @var string
     */
    protected $location;

    /**
     * @var string
     */
    protected $mediaMode;

    protected ?bool $e2ee;

    /**
     * @param array{archiveMode: string, location: string, mediaMode: string, e2ee: bool} $data
     */
    public function __construct($data = [])
    {
        $this->archiveMode = $data['archiveMode'] ?? ArchiveMode::MANUAL;
        $this->location = $data['location'] ?? null;
        $this->mediaMode = $data['mediaMode'] ?? $data['p2p.preferences'] ?? MediaMode::RELAYED;
        $this->e2ee = $data['e2ee'] ?? null;
    }

    public function getArchiveMode(): string
    {
        return $this->archiveMode;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getMediaMode(): string
    {
        return $this->mediaMode;
    }

    /**
     * Returns string because http_build_query casts to int
     * @return string|null
     */
    public function getE2ee(): ?string
    {
        return $this->e2ee === true ? 'true' : ($this->e2ee === false ? 'false' : null);
    }

    public function setArchiveMode(string $archiveMode): self
    {
        $this->archiveMode = $archiveMode;
        return $this;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;
        return $this;
    }
    
    public function setMediaMode(string $mediaMode): self
    {
        $this->mediaMode = $mediaMode;
        return $this;
    }

    public function setE2ee(?bool $e2ee): self
    {
        $this->e2ee = $e2ee;
        return $this;
    }
}