<?php

namespace VonageTest\Video;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Prophecy\Argument;
use Vonage\Video\CaptionOptions;
use Vonage\Video\Client;
use Vonage\Client\APIResource;
use PHPUnit\Framework\TestCase;
use Vonage\Client as VonageClient;
use Vonage\Video\Render;
use Vonage\Video\SessionOptions;
use Vonage\Video\WebsocketOptions;
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

class ClientTest extends TestCase
{
    use ProphecyTrait;
    use Psr7AssertionTrait;
    use HttpResponseTrait;

    protected APIResource $apiResource;

    /**
     * Sample Application ID to use in tests
     */
    protected string $applicationId = 'd5e57267-1bd2-4d76-aa53-c1c1542efc14';

    protected Client $client;

    /**
     * Sample Session ID to use in tests
     */
    protected string $sessionId = '2_999999999999999-MTYxODg4MTU5NjY3N35QY1VEUUl4MVhldEdKU2JCOWlyR2lHY3p-UH4';

    protected VonageClient|\Prophecy\Prophecy\ObjectProphecy $vonageClient;

    public function setUp(): void
    {
        $this->setResponseDir(__DIR__ . '/responses/');

        $this->vonageClient = $this->prophesize(VonageClient::class);
        $this->vonageClient->getRestUrl()->willReturn('https://rest.nexmo.com');
        $this->vonageClient->getApiUrl()->willReturn('https://api.nexmo.com');
        $this->vonageClient->getCredentials()->willReturn(new Keypair(file_get_contents(__DIR__ . '/private.key'), $this->applicationId));

        $this->apiResource = new APIResource();
        $this->apiResource
            ->setBaseUrl('https://video.api.vonage.com')
            ->setClient($this->vonageClient->reveal())
            ->setIsHAL(false)
            ->setCollectionName('items')
            ->setCollectionPrototype(new IterableAPICollection())
            ->setAuthHandlers([new KeypairHandler()])
        ;

        $this->client = new Client($this->apiResource);
    }

    public function testCanGenerateBasicClientToken(): void
    {
        $token = $this->client->generateClientToken('abcd');
        $parser = new Parser(new JoseEncoder());

        $claims = $parser->parse($token)->claims();
        $this->assertEquals($this->applicationId, $claims->get('application_id'));
        $this->assertEquals('session.connect', $claims->get('scope'));
        $this->assertEquals('abcd', $claims->get('session_id'));
        $this->assertEquals('video', $claims->get('sub'));
    }

    public function testCanGeneratePublisherOnlyClientToken(): void
    {
        $token = $this->client->generateClientToken('abcd', ['role' => Role::PUBLISHER_ONLY]);
        $parser = new Parser(new JoseEncoder());
        $claims = $parser->parse($token)->claims();
        $this->assertEquals($this->applicationId, $claims->get('application_id'));
        $this->assertEquals('session.connect', $claims->get('scope'));
        $this->assertEquals('abcd', $claims->get('session_id'));
        $this->assertEquals('video', $claims->get('sub'));
    }

    public function testCanGenerateClientTokenWithOptions(): void
    {
        $token = $this->client->generateClientToken('abcd', ['role' => Role::MODERATOR]);
        $parser = new Parser(new JoseEncoder());

        $claims = $parser->parse($token)->claims();
        $this->assertEquals($this->applicationId, $claims->get('application_id'));
        $this->assertEquals('session.connect', $claims->get('scope'));
        $this->assertEquals('abcd', $claims->get('session_id'));
        $this->assertEquals(Role::MODERATOR, $claims->get('role'));
    }

    public function testCanMuteAStream(): void
    {
        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertSame('https://video.api.vonage.com/v2/project/d5e57267-1bd2-4d76-aa53-c1c1542efc14/session/abcd/stream/1234/mute', $uriString);
            $this->assertSame('POST', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('project-details'));

        $response = $this->client->forceMuteStream('abcd', '1234');
        $this->assertEquals('12312', $response->getId());
    }

    public function testCanMuteAllStreams(): void
    {
        $excludedStreamIds = ['1234'];

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($excludedStreamIds) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertSame('https://video.api.vonage.com/v2/project/d5e57267-1bd2-4d76-aa53-c1c1542efc14/session/abcd/mute', $uriString);
            $this->assertSame('POST', $request->getMethod());
            $this->assertRequestJsonBodyContains('active', true, $request);
            $this->assertRequestJsonBodyContains('excludedStreamIds', $excludedStreamIds, $request);

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('project-details'));

        $response = $this->client->forceMuteAll('abcd', $excludedStreamIds);
        $this->assertEquals('12312', $response->getId());
    }

    public function testCanMuteMostStreams(): void
    {
        $excludedStreamIds = ['1234'];

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($excludedStreamIds) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertSame('https://video.api.vonage.com/v2/project/d5e57267-1bd2-4d76-aa53-c1c1542efc14/session/abcd/mute', $uriString);
            $this->assertSame('POST', $request->getMethod());
            $this->assertRequestJsonBodyContains('active', true, $request);
            $this->assertRequestJsonBodyContains('excludedStreamIds', $excludedStreamIds, $request);

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('project-details'));

        $response = $this->client->forceMuteAll('abcd', $excludedStreamIds);
        $this->assertEquals('12312', $response->getId());
    }

    public function testCanDisableMute(): void
    {
        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertSame('https://video.api.vonage.com/v2/project/d5e57267-1bd2-4d76-aa53-c1c1542efc14/session/abcd/mute', $uriString);
            $this->assertSame('POST', $request->getMethod());
            $this->assertRequestJsonBodyContains('active', false, $request);

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('project-details'));

        $response = $this->client->disableForceMute('abcd');
        $this->assertEquals('12312', $response->getId());
    }

    public function testCanDisconnectAClient(): void
    {
        $applicationId = $this->applicationId;
        $sessionId = 'abcd';
        $connectionId = '123';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $sessionId, $connectionId) {
            $this->assertSame('/v2/project/' . $applicationId . '/session/' . $sessionId . '/connection/' . $connectionId, $request->getUri()->getPath());
            $this->assertSame('DELETE', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('empty', 204));
        $this->client->disconnectClient($sessionId, $connectionId);
    }

    public function testCanCreateCustomLayout(): void
    {
        $applicationId = $this->applicationId;
        $archiveId = 'abcd';
        $stylesheet = 'div=color:red';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $archiveId, $stylesheet) {
            $this->assertSame('/v2/project/' . $applicationId . '/archive/' . $archiveId . '/layout', $request->getUri()->getPath());
            $this->assertSame('PUT', $request->getMethod());
            $this->assertRequestJsonBodyContains('type', 'custom', $request);
            $this->assertRequestJsonBodyContains('stylesheet', $stylesheet, $request);

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('empty', 204));
        $this->client->updateArchiveLayout($archiveId, ArchiveLayout::createCustom($stylesheet));
    }

    public function testCanSetScreenshareLayoutType(): void
    {
        $applicationId = $this->applicationId;
        $archiveId = 'abcd';
        $stylesheet = 'div=color:red';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $archiveId, $stylesheet) {
            $this->assertSame('/v2/project/' . $applicationId . '/archive/' . $archiveId . '/layout', $request->getUri()->getPath());
            $this->assertSame('PUT', $request->getMethod());
            $this->assertRequestJsonBodyContains('type', 'bestFit', $request);
            $this->assertRequestJsonBodyContains('screenshareType', 'pip', $request);

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('empty', 204));

        $layout = ArchiveLayout::getBestFit()->setScreenshareType(ArchiveLayout::LAYOUT_PIP);
        $this->client->updateArchiveLayout($archiveId, $layout);
    }

    public function testCanGetStream(): void
    {
        $applicationId = $this->applicationId;
        $sessionId = 'abcd';
        $streamId = '123';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $sessionId, $streamId) {
            $this->assertSame('/v2/project/' . $applicationId . '/session/' . $sessionId . '/stream/' . $streamId, $request->getUri()->getPath());
            $this->assertSame('GET', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('get-stream', 200));
        $stream = $this->client->getStream($sessionId, $streamId);

        $this->assertEquals("8b732909-0a06-46a2-8ea8-074e64d43422", $stream->getId());
        $this->assertEquals("camera", $stream->getVideoType());
        $this->assertEquals("", $stream->getName());
        $this->assertEquals(['full'], $stream->getLayoutClassList());
    }

    public function testCanListStreams(): void
    {
        $applicationId = $this->applicationId;
        $sessionId = 'abcd';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $sessionId) {
            $this->assertSame('/v2/project/' . $applicationId . '/session/' . $sessionId . '/stream', $request->getUri()->getPath());
            $this->assertSame('GET', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('list-streams', 200));
        $response = $this->client->listStreams($sessionId);

        $this->assertEquals(2, $response->count());
    }
}
