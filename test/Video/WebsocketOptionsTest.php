<?php

declare(strict_types=1);

namespace Vonage\Video\Test;

use PHPUnit\Framework\TestCase;
use Vonage\Video\WebsocketOptions;

class WebsocketOptionsTest extends TestCase
{
    public function testBidirectionalOptionCanBeSetAndRetrieved()
    {
        $options = new WebsocketOptions([
            'uri' => 'wss://example.com',
            'bidirectional' => true
        ]);
        $this->assertTrue($options->getBidirectional());
        $this->assertArrayHasKey('bidirectional', $options->toArray());
        $this->assertTrue($options->toArray()['bidirectional']);
    }

    public function testBidirectionalDefaultsToFalse()
    {
        $options = new WebsocketOptions(['uri' => 'wss://example.com']);
        $this->assertFalse($options->getBidirectional());
    }

    public function testSetBidirectionalFluently()
    {
        $options = new WebsocketOptions(['uri' => 'wss://example.com']);
        $this->assertSame($options, $options->setBidirectional(true));
        $this->assertTrue($options->getBidirectional());
    }
}
