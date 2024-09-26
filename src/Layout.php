<?php
declare(strict_types=1);

namespace Vonage\Video;

class Layout implements \JsonSerializable
{
    /**
     * @var string
     */
    public const LAYOUT_BESTFIT = 'bestFit';

    /**
     * @var string
     */
    public const LAYOUT_CUSTOM = 'custom';

    /**
     * @var string
     */
    public const LAYOUT_HORIZONTAL = 'horizontalPresentation';

    /**
     * @var string
     */
    public const LAYOUT_PIP = 'pip';

    /**
     * @var string
     */
    public const LAYOUT_VERTICAL = 'verticalPresentation';

    /**
     * Type of layout that we are sending
     * @var string
     */
    protected $type;

    /**
     * Type of layout to use for screen sharing
     * @var string
     */
    protected $screenshareType;

    /**
     * Custom stylesheet if our type is 'custom'
     * @var string|null
     */
    protected $stylesheet;

    private function __construct(string $type, ?string $stylesheet = null)
    {
        $this->type = $type;
        $this->stylesheet = $stylesheet;
    }

    /**
     * Returns a Layout object defining a custom layout type.
     *
     * @param array{stylesheet: string} $options
     */
    public static function createCustom(array $options): Layout
    {
        // unpack optional arguments (merging with default values) into named variables
        // NOTE: the default value of stylesheet=null will not pass validation, this essentially
        //       means that stylesheet is not optional. its still purposely left as part of the
        //       $options argument so that it can become truly optional in the future.
        $defaults = ['stylesheet' => null];
        $options = array_merge($defaults, array_intersect_key($options, $defaults));
        list($stylesheet) = array_values($options);

        return new Layout(static::LAYOUT_CUSTOM, $stylesheet);
    }

    /**
     * @param array{type: string, stylesheet?: string} $layoutData
     */
    public static function fromArray(array $layoutData): Layout
    {
        if (array_key_exists('stylesheet', $layoutData)) {
            return new Layout($layoutData['type'], $layoutData['stylesheet']);
        }

        return new Layout($layoutData['type']);
    }

    public static function getBestFit(): Layout
    {
        return new Layout(static::LAYOUT_BESTFIT);
    }

    public static function getPIP(): Layout
    {
        return new Layout(static::LAYOUT_PIP);
    }

    public static function getVerticalPresentation(): Layout
    {
        return new Layout(static::LAYOUT_VERTICAL);
    }

    public static function getHorizontalPresentation(): Layout
    {
        return new Layout(static::LAYOUT_HORIZONTAL);
    }

    public function setScreenshareType(string $screenshareType): Layout
    {
        if ($this->type === Layout::LAYOUT_BESTFIT) {
            $layouts = [
                Layout::LAYOUT_BESTFIT,
                Layout::LAYOUT_HORIZONTAL,
                Layout::LAYOUT_PIP,
                Layout::LAYOUT_VERTICAL
            ];

            if (!in_array($screenshareType, $layouts)) {
                throw new \RuntimeException('Screenshare type must be of a valid layout type');
            }

            $this->screenshareType = $screenshareType;
            return $this;
        }

        throw new \RuntimeException('Screenshare type cannot be set on a layout type other than bestFit');
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $data = array(
            'type' => $this->type
        );

        // omit 'stylesheet' property unless it is explicitly defined
        if (isset($this->stylesheet)) {
            $data['stylesheet'] = $this->stylesheet;
        }

        // omit 'screenshareType' property unless it is explicitly defined
        if (isset($this->screenshareType)) {
            $data['screenshareType'] = $this->screenshareType;
        }

        return $data;
    }
}
