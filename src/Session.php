<?php
declare(strict_types=1);

namespace Vonage\Video;

use Vonage\Entity\Hydrator\ArrayHydrateInterface;

class Session implements ArrayHydrateInterface
{
    /**
     * @var array<string, string>
     */
    protected $data;

    public function getArchiveMode(): string
    {
        return $this->data['archiveMode'];
    }

    public function getCreatedDate(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->data['create_dt']);
    }

    public function getLocation(): ?string
    {
        return $this->data['location'] ?? null;
    }

    public function getMediaMode(): string
    {
        return $this->data['mediaMode'] ?? $this->data['p2p.preference'];
    }

    public function getMediaServerUrl(): string
    {
        return $this->data['media_server_url'];
    }

    public function getProjectId(): string
    {
        return $this->data['project_id'];
    }

    public function getSessionId(): string
    {
        return $this->data['session_id'];
    }

    /**
     * @param array<string, string> $data 
     */
    public function fromArray(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return array<string, string> 
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
