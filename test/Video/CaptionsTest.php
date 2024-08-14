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

class CaptionsTest extends TestCase
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

    public function testCanStartCaptions(): void
    {
        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertEquals('https://video.api.vonage.com/v2/project/d5e57267-1bd2-4d76-aa53-c1c1542efc14/captions', $uriString);
            $this->assertSame('POST', $request->getMethod());
            $this->assertRequestJsonBodyContains('languageCode', 'en_GB', $request);
            $this->assertRequestJsonBodyContains('maxDuration', 1800, $request);
            $this->assertRequestJsonBodyContains('partialCaptions', true, $request);
            $this->assertRequestJsonBodyContains('statusCallbackUrl', 'https://send-status-to.me', $request);

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('captions-start'));

        $captionsData = [
            'languageCode' => 'en_GB',
            'maxDuration' => 1800,
            'partialCaptions' => true,
            'statusCallbackUrl' => 'https://send-status-to.me'
        ];

        $captionOptions = new CaptionOptions($captionsData);

        $result = $this->client->startCaptions('75173cd4-1847-4647-9701-4a61819bcee4', '75173cd4-1847-4647-9701-4a61819bcee4', $captionOptions);
        $this->assertEquals('7c0680fc-6274-4de5-a66f-d0648e8d3ac2', $result['captionsId']);
    }

    public function testCanStopCaptions(): void
    {
        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertEquals('https://video.api.vonage.com/v2/project/d5e57267-1bd2-4d76-aa53-c1c1542efc14/captions/7c0680fc-6274-4de5-a66f-d0648e8d3ac2/stop', $uriString);
            $this->assertSame('POST', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('captions-stop'));


        $result = $this->client->stopCaptions('7c0680fc-6274-4de5-a66f-d0648e8d3ac2');
        $this->assertTrue($result);
    }
}
