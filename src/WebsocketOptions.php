<?php

declare(strict_types=1);

namespace Vonage\Video;

use Vonage\Entity\Hydrator\ArrayHydrateInterface;

class WebsocketOptions implements ArrayHydrateInterface
{
    protected string $uri;

    protected array $streams = [];

    protected array $headers = [];

    protected ?int $audioRate = null;

    protected bool $bidirectional = false;

    public function __construct($data = [])
    {
        $this->fromArray($data);
    }

    public function getBidirectional(): bool
    {
        return $this->bidirectional;
    }

    public function setBidirectional(bool $bidirectional): self
    {
        $this->bidirectional = $bidirectional;
        return $this;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    public function getStreams(): array
    {
        return $this->streams;
    }

    public function setStreams(array $streams): self
    {
        $this->streams = $streams;
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function getAudioRate(): ?int
    {
        return $this->audioRate;
    }

    public function setAudioRate(?int $audioRate): self
    {
        if (isset($data['audioRate'])) {
            if ($data['audioRate'] !== 8000 && $data['audioRate'] !== 16000) {
                throw new \InvalidArgumentException('Audio Rate must be 8000 or 16000');
            }
            $this->audioRate = $data['audioRate'];
        }
        return $this;
    }

    public function fromArray(array $data): self
    {
        $this->uri = $data['uri'];

        if (isset($data['streams'])) {
            $this->streams = $data['streams'];
        }

        if (isset($data['headers'])) {
            $this->headers = $data['headers'];
        }

        if (isset($data['audioRate'])) {
            $this->audioRate = $data['audioRate'];
        }

        if (isset($data['bidirectional'])) {
            $this->bidirectional = (bool)$data['bidirectional'];
        }

        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'uri' => $this->getUri()
        ];

        if ($this->streams) {
            $data['streams'] = $this->getStreams();
        }

        if ($this->headers) {
            $data['headers'] = $this->getHeaders();
        }

        if ($this->audioRate) {
            $data['audio_rate'] = $this->getAudioRate();
        }

        if ($this->bidirectional) {
            $data['bidirectional'] = $this->getBidirectional();
        }

        return $data;
    }
}