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

class SessionTest extends TestCase
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

    public function testCanCreateSession(): void
    {
        $this->vonageClient->send(Argument::that(function () {
            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('create-session'));

        $session = $this->client->createSession();

        $this->assertSame($this->sessionId, $session->getSessionId());
        $this->assertSame(null, $session->getLocation());
        $this->assertSame(MediaMode::RELAYED, $session->getMediaMode());
        $this->assertSame(ArchiveMode::MANUAL, $session->getArchiveMode());
        $this->assertSame('99999999', $session->getProjectId());
        $this->assertEquals(new \DateTimeImmutable('2021-01-01 00:00:00'), $session->getCreatedDate());
        $this->assertSame('10.10.10.10', $session->getMediaServerUrl());
    }

    public function testCanCreateEndToEndEncryptedSession(): void
    {
        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $this->assertRequestFormBodyContains('e2ee', 'true', $request);
            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('create-session'));

        $sessionOptions = new SessionOptions(['e2ee' => true]);
        $session = $this->client->createSession($sessionOptions);

        $this->assertSame($this->sessionId, $session->getSessionId());
    }

    public function testCanSendSignalToEveryoneInSession(): void
    {
        $applicationId = $this->applicationId;
        $sessionId = $this->sessionId;

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $sessionId) {
            $this->assertRequestJsonBodyContains('type', 'car', $request);
            $this->assertRequestJsonBodyContains('data', 'sedan', $request);
            $this->assertSame('/v2/project/' . $applicationId . '/session/' . $sessionId . '/signal', $request->getUri()->getPath());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('empty'));

        $this->client->sendSignal($sessionId, 'car', 'sedan');
    }

    public function testCanSendSignalToSingleConnectionInSession(): void
    {
        $applicationId = $this->applicationId;
        $sessionId = $this->sessionId;
        $connectionId = 'iqu34ruqi';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $sessionId, $connectionId) {
            $this->assertRequestJsonBodyContains('type', 'car', $request);
            $this->assertRequestJsonBodyContains('data', 'sedan', $request);
            $this->assertSame('/v2/project/' . $applicationId . '/session/' . $sessionId . '/connection/' . $connectionId . '/signal', $request->getUri()->getPath());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('empty'));

        $this->client->sendSignal($sessionId, 'car', 'sedan', $connectionId);
    }
}
