<?php

declare(strict_types=1);

namespace Vonage\Video;

use Vonage\Entity\Hydrator\ArrayHydrateInterface;

/**
 * Represents an Experience Composer render of a Vonage Video session.
 */
class Render implements ArrayHydrateInterface
{
    private mixed $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function __get($name)
    {
        return match ($name) {
            'id', 'sessionId', 'projectId', 'createdAt', 'updatedAt', 'url', 'resolution', 'status', 'streamId' => $this->data[$name],
            default => null,
        };
    }

    public function fromArray(array $data): void
    {
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}