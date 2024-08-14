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

class BroadcastTest extends TestCase
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
    
    public function testCanStartABroadcast(): void
    {
        $applicationId = $this->applicationId;
        $sessionId = $this->sessionId;

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $sessionId) {
            $this->assertSame('/v2/project/' . $applicationId . '/broadcast', $request->getUri()->getPath());
            $this->assertSame('POST', $request->getMethod());

            $request->getBody()->rewind();
            $body = json_decode($request->getBody()->getContents(), true);
            $this->assertSame($sessionId, $body['sessionId']);
            $this->assertEquals(2, count($body['outputs']['rtmp']));

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('broadcast-start'));

        $expected = json_decode($this->getResponse('broadcast-start')->getBody()->getContents(), true);

        $config = new BroadcastConfig($this->sessionId);
        $config
            ->setResolution(Resolution::RESOLUTION_LANDSCAPE_SD)
            ->setStreamMode('auto')
            ->setOutputConfig(
                (new OutputConfig())
                    ->addRTMPStream(new Stream('myfoostream', 'rtmps://myfooserver/myfooapp', 'foo'))
                    ->addRTMPStream(new Stream('mybarstream', 'rtmp://mybarserver/mybarapp', 'bar'))
            );
        $broadcast = $this->client->startBroadcast($config);

        $this->assertEquals($expected['id'], $broadcast->getId());
        $this->assertEquals($expected['sessionId'], $broadcast->getSessionId());
        $this->assertEquals($this->applicationId, $broadcast->getApplicationId());
        $this->assertEquals((new \DateTimeImmutable())->setTimestamp($expected['createdAt'])->format('Y-m-d H:i:s'), $broadcast->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->assertEquals((new \DateTimeImmutable())->setTimestamp($expected['updatedAt'])->format('Y-m-d H:i:s'), $broadcast->getUpdatedAt()->format('Y-m-d H:i:s'));
        $this->assertEquals($expected['maxDuration'], $broadcast->getMaxDuration());
        $this->assertEquals($expected['maxBitrate'], $broadcast->getMaxBitrate());
        $this->assertEquals($expected['resolution'], $broadcast->getResolution());
    }

    public function testCanStopABroadcast(): void
    {
        $applicationId = $this->applicationId;
        $expected = json_decode($this->getResponse('broadcast-stop')->getBody()->getContents(), true);

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $expected) {
            $this->assertSame('/v2/project/' . $applicationId . '/broadcast/' . $expected['id'] . '/stop', $request->getUri()->getPath());
            $this->assertSame('POST', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('broadcast-stop'));

        $broadcast = $this->client->stopBroadcast($expected['id']);
        $this->assertEquals($expected['id'], $broadcast->getId());
        $this->assertEquals($expected['sessionId'], $broadcast->getSessionId());
        $this->assertEquals($this->applicationId, $broadcast->getApplicationId());
        $this->assertEquals((new \DateTimeImmutable())->setTimestamp($expected['createdAt'])->format('Y-m-d H:i:s'), $broadcast->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->assertEquals((new \DateTimeImmutable())->setTimestamp($expected['updatedAt'])->format('Y-m-d H:i:s'), $broadcast->getUpdatedAt()->format('Y-m-d H:i:s'));
        $this->assertEquals($expected['maxDuration'], $broadcast->getMaxDuration());
        $this->assertEquals($expected['maxBitrate'], $broadcast->getMaxBitrate());
        $this->assertEquals($expected['resolution'], $broadcast->getResolution());
    }

    public function testCanListBroadcasts(): void
    {
        $applicationId = $this->applicationId;
        $expected = json_decode($this->getResponse('broadcast-list')->getBody()->getContents(), true);

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $expected) {
            $this->assertSame('/v2/project/' . $applicationId . '/broadcast', $request->getUri()->getPath());
            $this->assertSame('GET', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(2)->willReturn($this->getResponse('broadcast-list'));

        $list = $this->client->listBroadcasts();
        
        $this->assertEquals($expected['count'], $list->count());
        $count = 0;
        foreach ($list as $broadcast) {
            $this->assertEquals($expected[$count]['id'], $broadcast->getId());
            $this->assertEquals($expected[$count]['sessionId'], $broadcast->getSessionId());
            $this->assertEquals($this->applicationId, $broadcast->getApplicationId());
            $this->assertEquals((new \DateTimeImmutable())->setTimestamp($expected[$count]['createdAt'])->format('Y-m-d H:i:s'), $broadcast->getCreatedAt()->format('Y-m-d H:i:s'));
            $this->assertEquals((new \DateTimeImmutable())->setTimestamp($expected[$count]['updatedAt'])->format('Y-m-d H:i:s'), $broadcast->getUpdatedAt()->format('Y-m-d H:i:s'));
            $this->assertEquals($expected[$count]['maxDuration'], $broadcast->getMaxDuration());
            $this->assertEquals($expected[$count]['maxBitrate'], $broadcast->getMaxBitrate());
            $this->assertEquals($expected[$count]['resolution'], $broadcast->getResolution());
        }
    }

    public function testCanAddStreamToBroadcast(): void
    {
        $applicationId = $this->applicationId;
        $streamId = '12312312-3811-4726-b508-e41a0f96c68f';
        $broadcastId = 'd95f6496-df6e-4f49-86d6-832e00303602';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $broadcastId, $streamId) {
            $this->assertSame('/v2/project/' . $applicationId . '/broadcast/' . $broadcastId . '/streams', $request->getUri()->getPath());
            $this->assertSame('PATCH', $request->getMethod());

            $request->getBody()->rewind();
            $body = json_decode($request->getBody()->getContents(), true);
            $this->assertSame($streamId, $body['addStream']);

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('empty'));

        $this->client->addStreamToBroadcast($broadcastId, $streamId);
    }
}
