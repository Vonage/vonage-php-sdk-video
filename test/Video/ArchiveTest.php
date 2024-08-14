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

class ArchiveTest extends TestCase
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

    public function testCanStartArchive(): void
    {
        $sessionId = $this->sessionId;
        $applicationId = $this->applicationId;

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId) {
            $this->assertSame('/v2/project/' . $applicationId . '/archive', $request->getUri()->getPath());
            $this->assertSame('POST', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('archive-start'));

        $expected = json_decode($this->getResponse('archive-start')->getBody()->getContents(), true);
        $archive = $this->client->startArchive(new ArchiveConfig($sessionId));

        $this->assertSame($expected['id'], $archive->getId());
        $this->assertSame($expected['status'], $archive->getStatus());
        $this->assertSame($expected['name'], $archive->getName());
        $this->assertSame($expected['reason'], $archive->getReason());
        $this->assertSame($expected['sessionId'], $archive->getSessionId());
        $this->assertSame($expected['applicationId'], $archive->getApplicationId());
        $this->assertSame($expected['createdAt'], $archive->getCreatedAt());
        $this->assertSame($expected['size'], $archive->getSize());
        $this->assertSame($expected['duration'], $archive->getDuration());
        $this->assertSame($expected['outputMode'], $archive->getOutputMode());
        $this->assertSame($expected['hasAudio'], $archive->getHasAudio());
        $this->assertSame($expected['hasVideo'], $archive->getHasVideo());
        $this->assertSame($expected['sha256sum'], $archive->getSha256Sum());
        $this->assertSame($expected['password'], $archive->getPassword());
        $this->assertSame($expected['updatedAt'], $archive->getUpdatedAt());
        $this->assertSame($expected['resolution'], $archive->getResolution());
        $this->assertSame($expected['event'], $archive->getEvent());
        $this->assertSame($expected['url'], $archive->getUrl());
    }

    public function testHandlesStartingArchiveOnceArchiveIsAlreadyStarted(): void
    {
        $this->expectException(Request::class);
        $this->expectExceptionMessage('HTTP 409 Conflict');

        $applicationId = $this->applicationId;

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId) {
            $this->assertSame('/v2/project/' . $applicationId . '/archive', $request->getUri()->getPath());
            $this->assertSame('POST', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('archive-start-conflict', 409));
        $archive = $this->client->startArchive(new ArchiveConfig($this->sessionId));
    }

    public function testHandlesNoClientsConnectedErrorWhenStartingArchive(): void
    {
        $this->expectException(Request::class);
        $this->expectExceptionMessage('Unexpected error');

        $applicationId = $this->applicationId;

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId) {
            $this->assertSame('/v2/project/' . $applicationId . '/archive', $request->getUri()->getPath());
            $this->assertSame('POST', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('archive-start-no-clients', 404));
        $archive = $this->client->startArchive(new ArchiveConfig($this->sessionId));
    }

    public function testCanStopArchive(): void
    {
        $archiveId = '506efa9e-7849-410e-bf76-dafd80b1d94e';
        $applicationId = $this->applicationId;

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $archiveId) {
            $this->assertSame('/v2/project/' . $applicationId . '/archive/' . $archiveId . '/stop', $request->getUri()->getPath());
            $this->assertSame('POST', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('archive-stop'));

        $expected = json_decode($this->getResponse('archive-stop')->getBody()->getContents(), true);
        $archive = $this->client->stopArchive($archiveId);

        $this->assertSame($expected['id'], $archive->getId());
        $this->assertSame($expected['status'], $archive->getStatus());
        $this->assertSame($expected['name'], $archive->getName());
        $this->assertSame($expected['reason'], $archive->getReason());
        $this->assertSame($expected['sessionId'], $archive->getSessionId());
        $this->assertSame($expected['applicationId'], $archive->getApplicationId());
        $this->assertSame($expected['createdAt'], $archive->getCreatedAt());
        $this->assertSame($expected['size'], $archive->getSize());
        $this->assertSame($expected['duration'], $archive->getDuration());
        $this->assertSame($expected['outputMode'], $archive->getOutputMode());
        $this->assertSame($expected['hasAudio'], $archive->getHasAudio());
        $this->assertSame($expected['hasVideo'], $archive->getHasVideo());
        $this->assertSame($expected['sha256sum'], $archive->getSha256Sum());
        $this->assertSame($expected['password'], $archive->getPassword());
        $this->assertSame($expected['updatedAt'], $archive->getUpdatedAt());
        $this->assertSame($expected['resolution'], $archive->getResolution());
        $this->assertSame($expected['event'], $archive->getEvent());
        $this->assertSame($expected['url'], $archive->getUrl());
    }

    public function testCanGetArchive(): void
    {
        $archiveId = '506efa9e-7849-410e-bf76-dafd80b1d94e';
        $applicationId = $this->applicationId;

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $archiveId) {
            $this->assertSame('/v2/project/' . $applicationId . '/archive/' . $archiveId, $request->getUri()->getPath());
            $this->assertSame('GET', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('get-archive'));

        $expected = json_decode($this->getResponse('get-archive')->getBody()->getContents(), true);
        $archive = $this->client->getArchive($archiveId);

        $this->assertSame($expected['id'], $archive->getId());
        $this->assertSame($expected['status'], $archive->getStatus());
        $this->assertSame($expected['name'], $archive->getName());
        $this->assertSame($expected['reason'], $archive->getReason());
        $this->assertSame($expected['sessionId'], $archive->getSessionId());
        $this->assertSame($expected['applicationId'], $archive->getApplicationId());
        $this->assertSame($expected['createdAt'], $archive->getCreatedAt());
        $this->assertSame($expected['size'], $archive->getSize());
        $this->assertSame($expected['duration'], $archive->getDuration());
        $this->assertSame($expected['outputMode'], $archive->getOutputMode());
        $this->assertSame($expected['hasAudio'], $archive->getHasAudio());
        $this->assertSame($expected['hasVideo'], $archive->getHasVideo());
        $this->assertSame($expected['sha256sum'], $archive->getSha256Sum());
        $this->assertSame($expected['password'], $archive->getPassword());
        $this->assertSame($expected['updatedAt'], $archive->getUpdatedAt());
        $this->assertSame($expected['resolution'], $archive->getResolution());
        $this->assertSame($expected['event'], $archive->getEvent());
        $this->assertSame($expected['url'], $archive->getUrl());
    }

    public function testCanGetAllArchives(): void
    {
        $applicationId = $this->applicationId;

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId) {
            $this->assertSame('/v2/project/' . $applicationId . '/archive', $request->getUri()->getPath());
            $this->assertSame('GET', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(2)->willReturn($this->getResponse('list-archives'), $this->getResponse('empty'));

        $expected = json_decode($this->getResponse('list-archives')->getBody()->getContents(), true);
        $archives = $this->client->listArchives();

        $this->assertCount(2, $archives);
        $key = 0;
        foreach ($archives as $archive) {
            $this->assertSame($expected['items'][$key]['id'], $archive->getId());
            $this->assertSame($expected['items'][$key]['status'], $archive->getStatus());
            $this->assertSame($expected['items'][$key]['name'], $archive->getName());
            $this->assertSame($expected['items'][$key]['reason'], $archive->getReason());
            $this->assertSame($expected['items'][$key]['sessionId'], $archive->getSessionId());
            $this->assertSame($expected['items'][$key]['applicationId'], $archive->getApplicationId());
            $this->assertSame($expected['items'][$key]['createdAt'], $archive->getCreatedAt());
            $this->assertSame($expected['items'][$key]['size'], $archive->getSize());
            $this->assertSame($expected['items'][$key]['duration'], $archive->getDuration());
            $this->assertSame($expected['items'][$key]['outputMode'], $archive->getOutputMode());
            $this->assertSame($expected['items'][$key]['hasAudio'], $archive->getHasAudio());
            $this->assertSame($expected['items'][$key]['hasVideo'], $archive->getHasVideo());
            $this->assertSame($expected['items'][$key]['sha256sum'], $archive->getSha256Sum());
            $this->assertSame($expected['items'][$key]['password'], $archive->getPassword());
            $this->assertSame($expected['items'][$key]['updatedAt'], $archive->getUpdatedAt());
            $this->assertSame($expected['items'][$key]['resolution'], $archive->getResolution());
            $this->assertSame($expected['items'][$key]['event'], $archive->getEvent());
            $this->assertSame($expected['items'][$key]['url'], $archive->getUrl());
            $key++;
        }
    }

    public function testCanDeleteAnArchive(): void
    {
        $applicationId = $this->applicationId;
        $archiveId = 'abcd';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $archiveId) {
            $this->assertSame('/v2/project/' . $applicationId . '/archive/' . $archiveId, $request->getUri()->getPath());
            $this->assertSame('DELETE', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('empty', 204));
        $this->client->deleteArchive($archiveId);
    }

    public function testCanUpdateArchiveLayout(): void
    {
        $applicationId = $this->applicationId;
        $archiveId = 'abcd';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $archiveId) {
            $this->assertSame('/v2/project/' . $applicationId . '/archive/' . $archiveId . '/layout', $request->getUri()->getPath());
            $this->assertSame('PUT', $request->getMethod());
            $this->assertRequestJsonBodyContains('type', 'bestFit', $request);

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('empty', 204));
        $this->client->updateArchiveLayout($archiveId, ArchiveLayout::getBestFit());
    }
}
