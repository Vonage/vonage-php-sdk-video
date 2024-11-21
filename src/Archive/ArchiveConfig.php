<?php
declare(strict_types=1);

namespace Vonage\Video\Archive;

use Vonage\Video\Layout;
use Vonage\Video\Resolution;

class ArchiveConfig implements \JsonSerializable
{
    /**
     * @var string
     */
    const OUTPUT_MODE_COMPOSED = "composed";

    /**
     * @var string
     */
    const OUTPUT_MODE_INDIVIDUAL = "individual";

    const MIN_BITRATE = 1000000;

    const MAX_BITRATE = 6000000;

    protected string $sessionId;

    protected bool $hasAudio = true;

    protected bool $hasVideo = true;

    protected ?int $maxBitrate = null;

    /**
     * @var Layout
     */
    protected $layout;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $outputMode;

    /**
     * @var string
     */
    protected $resolution;

    public function __construct(string $sessionId)
    {
        $this->sessionId = $sessionId;
    }

    public function getHasAudio(): bool
    {
        return $this->hasAudio;
    }

    public function setHasAudio(bool $hasAudio): self
    {
        $this->hasAudio = $hasAudio;

        return $this;
    }

    public function getHasVideo(): bool
    {
        return $this->hasVideo;
    }

    public function setHasVideo(bool $hasVideo): self
    {
        $this->hasVideo = $hasVideo;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getOutputMode(): ?string
    {
        return $this->outputMode;
    }

    public function setOutputMode(string $outputMode): self
    {
        $whitelist = [
            static::OUTPUT_MODE_COMPOSED,
            static::OUTPUT_MODE_INDIVIDUAL,
        ];

        if (!in_array($outputMode, $whitelist)) {
            throw new \InvalidArgumentException('Invalid output mode for archive');
        }

        $this->outputMode = $outputMode;

        return $this;
    }

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function setResolution(string $resolution): self
    {
        Resolution::isValid($resolution);

        $this->resolution = $resolution;

        return $this;
    }

    public function getLayout(): ?Layout
    {
        return $this->layout;
    }

    public function setLayout(Layout $layout): self
    {
        $this->layout = $layout;

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function getMaxBitrate(): ?int
    {
        return $this->maxBitrate;
    }

    public function setMaxBitrate(?int $maxBitrate): ArchiveConfig
    {
        $range = [
            'options' => [
                'min_range' => self::MIN_BITRATE,
                'max_range' => self::MAX_BITRATE
            ]
        ];

        if (!filter_var($maxBitrate, FILTER_VALIDATE_INT, $range)) {
            throw new \OutOfBoundsException('MaxBitrate ' . $maxBitrate . ' is not valid');
        }

        $this->maxBitrate = $maxBitrate;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'sessionId' => $this->getSessionId(),
            'hasAudio' => $this->getHasAudio(),
            'hasVideo' => $this->getHasVideo(),
        ];

        if ($this->getLayout()) {
            $data['layout'] = $this->getLayout();
        }

        if ($this->getName()) {
            $data['name'] = $this->getName();
        }

        if ($this->getOutputMode()) {
            $data['outputMode'] = $this->getOutputMode();
        }

        if ($this->getResolution()) {
            $data['resolution'] = $this->getResolution();
        }

        if ($this->getMaxBitrate()) {
            $data['maxBitrate'] = $this->getMaxBitrate();
        }

        return $data;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }
}
