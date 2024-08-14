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

class ExperienceComposerTest extends TestCase
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

    public function testCanStartExperienceComposerSessions(): void
    {
        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertEquals(
                'https://video.api.vonage.com/v2/project/d5e57267-1bd2-4d76-aa53-c1c1542efc14/render',
                $uriString
            );

            $this->assertSame('POST', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('render-start'));

        $render = $this->client->startExperienceComposerSession(
            '2_MX4xMDBfjE0Mzc2NzY1NDgwMTJ-TjMzfn4',
            'e2343f23456g34709d2443a234',
            'https://webapp.customer.com',
            [
                'name' => 'Composed stream for live event'
            ],
            '1280x720'.
            2900,
        );

        $this->assertInstanceOf(Render::class, $render);
        $this->assertEquals('2_MX4xMDBfjE0Mzc2NzY1NDgwMTJ-TjMzfn4', $render->sessionId);
        $this->assertEquals('started', $render->status);
    }

    public function testCanGetExperienceComposerSession(): void
    {
        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertEquals('https://video.api.vonage.com/v2/project/d5e57267-1bd2-4d76-aa53-c1c1542efc14/render/80abaf0d-25a3-4efc-968f-6268d620668d', $uriString);
            $this->assertSame('GET', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('render-get'));

        $render = $this->client->getExperienceComposerSession('80abaf0d-25a3-4efc-968f-6268d620668d');

        $this->assertInstanceOf(Render::class, $render);
        $this->assertEquals('1_MX4yNzA4NjYxMn5-MTU0NzA4MDUyMTEzNn5sOXU5ZnlWYXplRnZGblV4RUo3dXJpZk1-fg', $render->sessionId);
        $this->assertEquals('failed', $render->status);
    }

    public function testCanStopExperienceComposerSession(): void
    {
        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertEquals('https://video.api.vonage.com/v2/project/d5e57267-1bd2-4d76-aa53-c1c1542efc14/render/80abaf0d-25a3-4efc-968f-6268d620668d', $uriString);
            $this->assertSame('DELETE', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('render-stop'));

        $response = $this->client->stopExperienceComposerSession('80abaf0d-25a3-4efc-968f-6268d620668d');

        $this->assertTrue($response);
    }

    public function testCannotStopUnknownExperienceComposerSession(): void
    {
        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertEquals('https://video.api.vonage.com/v2/project/d5e57267-1bd2-4d76-aa53-c1c1542efc14/render/80abaf0d-25a3-4efc-968f-6268d620668d', $uriString);
            $this->assertSame('DELETE', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('render-stop-404', 404));

        $response = $this->client->stopExperienceComposerSession('80abaf0d-25a3-4efc-968f-6268d620668d');

        $this->assertFalse($response);
    }

    public function testCanListExperienceComposerSession(): void
    {
        $requestCount = 0;
        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use (&$requestCount) {
            $requestCount++;
            $uri = $request->getUri();
            $uriString = $uri->__toString();

            if ($requestCount == 1) {
                $this->assertEquals('https://video.api.vonage.com/v2/project/d5e57267-1bd2-4d76-aa53-c1c1542efc14/render?offset=0&count=50', $uriString);
            }

            if ($requestCount == 2) {
                $this->assertEquals('https://video.api.vonage.com/v2/project/d5e57267-1bd2-4d76-aa53-c1c1542efc14/render?offset=2&count=50', $uriString);
            }

            $this->assertSame('GET', $request->getMethod());

            return true;
        }))->willReturn($this->getResponse('render-list'), $this->getResponse('render-list-finished'));

        $response = $this->client->listExperienceComposerSessions(null);

        foreach ($response as $item) {
            $this->assertInstanceOf(Render::class, $item);
        }
    }
}
