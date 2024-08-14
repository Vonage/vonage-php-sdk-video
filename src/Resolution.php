<?php
declare(strict_types=1);

namespace Vonage\Video;

class Resolution
{
/**
     * @var string
     */
    const RESOLUTION_LANDSCAPE_SD = "640x480";

    /**
     * @var string
     */
    const RESOLUTION_LANDSCAPE_HD = "1280x720";

    /**
     * @var string
     */
    const RESOLUTION_LANDSCAPE_UHD = "1920x1080";

    /**
     * @var string
     */
    const RESOLUTION_PORTRAIT_SD = "480x640";

    /**
     * @var string
     */
    const RESOLUTION_PORTRAIT_HD = "720x1280";

    /**
     * @var string
     */
    const RESOLUTION_PORTRAIT_UHD = "1080x1920";

    static public function isValid(string $resolution): void
    {
        $whitelist = [
            static::RESOLUTION_LANDSCAPE_HD,
            static::RESOLUTION_LANDSCAPE_SD,
            static::RESOLUTION_LANDSCAPE_UHD,
            static::RESOLUTION_PORTRAIT_HD,
            static::RESOLUTION_PORTRAIT_SD,
            static::RESOLUTION_PORTRAIT_UHD,
        ];

        if (!in_array($resolution, $whitelist)) {
            throw new \InvalidArgumentException('Invalid resolution specified for archive');
        }
    }
}
