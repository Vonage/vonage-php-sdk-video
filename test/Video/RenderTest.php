<?php

namespace VonageTest\Video;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Prophecy\Argument;
use Vonage\Video\Client;
use Vonage\Client\APIResource;
use PHPUnit\Framework\TestCase;
use Vonage\Client as VonageClient;
use Vonage\Video\Render;
use VonageTest\HttpResponseTrait;
use VonageTest\Psr7AssertionTrait;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestInterface;
use Vonage\Client\Credentials\Handler\KeypairHandler;
use Vonage\Client\Credentials\Keypair;
use Vonage\Client\Exception\Request;
use Vonage\Video\Archive\ArchiveConfig;
use Vonage\Video\Archive\ArchiveLayout;
use Vonage\Video\Archive\ArchiveMode;
use Vonage\Video\Broadcast\BroadcastConfig;
use Vonage\Video\Broadcast\OutputConfig;
use Vonage\Video\Broadcast\Stream;
use Vonage\Video\Entity\IterableAPICollection;
use Vonage\Video\MediaMode;
use Vonage\Video\Role;
use Vonage\Video\Resolution;

class RenderTest extends TestCase
{
    use ProphecyTrait;
    use Psr7AssertionTrait;
    use HttpResponseTrait;

    protected APIResource $apiResource;

    protected Client $client;

    protected VonageClient|\Prophecy\Prophecy\ObjectProphecy $vonageClient;

    public function testCanHydrateFromPayload(): void
    {
        $payload = [
            'id' => '1248e7070b81464c9789f46ad10e7764',
            'sessionId' => '2_MX4xMDBfjE0Mzc2NzY1NDgwMTJ-TjMzfn4',
            'projectId' => 'e2343f23456g34709d2443a234',
            'createdAt' => 1437676551000,
            'updatedAt' => 1437676551000,
            'url' => 'https://webapp.customer.com',
            'resolution' => '1280x720',
            'status' => 'started',
            'streamId' => 'e32445b743678c98230f238'
        ];

        $render = new Render($payload);

        $this->assertEquals('1248e7070b81464c9789f46ad10e7764', $render->id);
        $this->assertEquals('2_MX4xMDBfjE0Mzc2NzY1NDgwMTJ-TjMzfn4', $render->sessionId);
        $this->assertEquals('e2343f23456g34709d2443a234', $render->projectId);
        $this->assertEquals(1437676551000, $render->createdAt);
        $this->assertEquals(1437676551000, $render->updatedAt);
        $this->assertEquals('https://webapp.customer.com', $render->url);
        $this->assertEquals('1280x720', $render->resolution);
        $this->assertEquals('started', $render->status);
        $this->assertEquals('e32445b743678c98230f238', $render->streamId);
    }
}
