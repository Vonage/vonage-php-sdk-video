<?php

namespace VonageTest\Video;

use Prophecy\Argument;
use Vonage\Video\Client;
use Vonage\Client\APIResource;
use Laminas\Diactoros\Response;
use Lcobucci\JWT\Configuration;
use PHPUnit\Framework\TestCase;
use Vonage\Client as VonageClient;
use VonageTest\Psr7AssertionTrait;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestInterface;
use Vonage\Client\Credentials\Keypair;
use Vonage\Client\Exception\Request;
use Vonage\Video\Archive\ArchiveConfig;
use Vonage\Video\Archive\ArchiveLayout;
use Vonage\Video\Archive\ArchiveMode;
use Vonage\Video\Entity\IterableAPICollection;
use Vonage\Video\MediaMode;
use Vonage\Video\Role;

class ClientTest extends TestCase
{
    use ProphecyTrait;
    use Psr7AssertionTrait;

    /**
     * @var APIResource
     */
    protected $apiResource;

    /**
     * Sample Application ID to use in tests
     * @var string
     */
    protected $applicationId = 'd5e57267-1bd2-4d76-aa53-c1c1542efc14';

    /**
     * @var Client
     */
    protected $client;

    /**
     * Sample Session ID to use in tests
     * @var string
     */
    protected $sessionId = '2_999999999999999-MTYxODg4MTU5NjY3N35QY1VEUUl4MVhldEdKU2JCOWlyR2lHY3p-UH4';

    protected $vonageClient;

    public function setUp(): void
    {
        $this->vonageClient = $this->prophesize(VonageClient::class);
        $this->vonageClient->getRestUrl()->willReturn('https://rest.nexmo.com');
        $this->vonageClient->getApiUrl()->willReturn('https://api.nexmo.com');
        $this->vonageClient->getCredentials()->willReturn(new Keypair(file_get_contents(__DIR__ . '/files/private.test.key'), $this->applicationId));

        $this->apiResource = new APIResource();
        $this->apiResource
            ->setBaseUri('/')
            ->setClient($this->vonageClient->reveal())
            ->setIsHAL(false)
            ->setCollectionName('items')
            ->setCollectionPrototype(new IterableAPICollection())
        ;

        $this->client = new Client($this->apiResource);
    }

    public function testCanCreateSession()
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

    public function testCanSendSignalToEveryoneInSession()
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

    public function testCanSendSignalToSingleConnectionInSession()
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

    public function testCanStartArchive()
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

    public function testHandlesStartingArchiveOnceArchiveIsAlreadyStarted()
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

    public function testCanStopArchive()
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

    public function testCanGetArchive()
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

    public function testCanGetAllArchives()
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

    public function testCanGenerateBasicClientToken()
    {
        $token = $this->client->generateClientToken('abcd');
        $parser = Configuration::forUnsecuredSigner()->parser();
        $claims = $parser->parse($token)->claims();
        $this->assertEquals($this->applicationId, $claims->get('application_id'));
        $this->assertEquals('session.connect', $claims->get('scope'));
        $this->assertEquals('abcd', $claims->get('session_id'));
    }

    public function testCanGenerateClientTokenWithOptions()
    {
        $token = $this->client->generateClientToken('abcd', ['role' => Role::MODERATOR]);
        $parser = Configuration::forUnsecuredSigner()->parser();
        $claims = $parser->parse($token)->claims();
        $this->assertEquals($this->applicationId, $claims->get('application_id'));
        $this->assertEquals('session.connect', $claims->get('scope'));
        $this->assertEquals('abcd', $claims->get('session_id'));
        $this->assertEquals(Role::MODERATOR, $claims->get('role'));
    }

    public function testCanMuteAStream()
    {
        $applicationId = $this->applicationId;
        $sessionId = 'abcd';
        $streamId = '1234';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $sessionId, $streamId) {
            $this->assertSame('/v2/project/' . $applicationId . '/session/' . $sessionId . '/stream/' . $streamId . '/mute', $request->getUri()->getPath());
            $this->assertSame('POST', $request->getMethod());

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('project-details'));

        $response = $this->client->forceMuteStream('abcd', '1234');
        $this->assertEquals('12312', $response->getId());
    }

    public function testCanMuteAllStreams()
    {
        $applicationId = $this->applicationId;
        $sessionId = 'abcd';
        $excludedStreamIds = [];

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $sessionId, $excludedStreamIds) {
            $this->assertSame('/v2/project/' . $applicationId . '/session/' . $sessionId . '/mute', $request->getUri()->getPath());
            $this->assertSame('POST', $request->getMethod());
            $this->assertRequestJsonBodyContains('active', true, $request);
            $this->assertRequestJsonBodyContains('excludedStreamIds', $excludedStreamIds, $request);

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('project-details'));

        $response = $this->client->forceMuteAll('abcd');
        $this->assertEquals('12312', $response->getId());
    }

    public function testCanMuteMostStreams()
    {
        $applicationId = $this->applicationId;
        $sessionId = 'abcd';
        $excludedStreamIds = ['1234'];

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $sessionId, $excludedStreamIds) {
            $this->assertSame('/v2/project/' . $applicationId . '/session/' . $sessionId . '/mute', $request->getUri()->getPath());
            $this->assertSame('POST', $request->getMethod());
            $this->assertRequestJsonBodyContains('active', true, $request);
            $this->assertRequestJsonBodyContains('excludedStreamIds', $excludedStreamIds, $request);

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('project-details'));

        $response = $this->client->forceMuteAll('abcd', $excludedStreamIds);
        $this->assertEquals('12312', $response->getId());
    }

    public function testCanDisableMute()
    {
        $applicationId = $this->applicationId;
        $sessionId = 'abcd';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) use ($applicationId, $sessionId) {
            $this->assertSame('/v2/project/' . $applicationId . '/session/' . $sessionId . '/mute', $request->getUri()->getPath());
            $this->assertSame('POST', $request->getMethod());
            $this->assertRequestJsonBodyContains('active', false, $request);

            return true;
        }))->shouldBeCalledTimes(1)->willReturn($this->getResponse('project-details'));

        $response = $this->client->disableForceMute('abcd');
        $this->assertEquals('12312', $response->getId());
    }

    public function testCanDisconnectAClient()
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

    public function testCanUpdateArchiveLayout()
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

    public function testCanCreateCustomLayout()
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

    public function testCanSetScreenshareLayoutType()
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

    public function testCanDeleteAnArchive()
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

    protected function getResponse(string $type = 'success', int $status = 200): Response
    {
        return new Response(fopen(__DIR__ . '/responses/' . $type . '.json', 'rb'), $status);
    }
}
