<?php
declare(strict_types=1);

namespace Vonage\Video;

use Vonage\Client\APIClient;
use Vonage\Client\APIResource;
use Vonage\Client\Exception\Request;
use Vonage\Entity\Filter\EmptyFilter;
use Vonage\Entity\Hydrator\ArrayHydrator;
use Vonage\Video\Broadcast\Broadcast;
use Vonage\Client\Credentials\Container;
use Vonage\Client\Credentials\Keypair;
use Vonage\Entity\Filter\FilterInterface;
use Vonage\Video\Broadcast\BroadcastConfig;
use Vonage\Entity\Hydrator\ConstructorHydrator;
use Vonage\Entity\IterableAPICollection;
use Vonage\JWT\TokenGenerator;
use Vonage\Video\Archive\Archive;
use Vonage\Video\Archive\ArchiveConfig;
use Vonage\Video\Archive\ArchiveLayout;
use Vonage\Client\Credentials\CredentialsInterface;
use Vonage\Video\Render;

class Client implements APIClient
{
    /**
     * @var APIResource
     */
    protected $apiResource;

    /**
     * @var Keypair
     */
    protected $credentials;

    public function __construct(APIResource $apiResource)
    {
        $this->apiResource = $apiResource;
        $this->credentials = $this->extractCredentials(Keypair::class, $this->getAPIResource()->getClient()->getCredentials());
    }

    public function getAPIResource(): APIResource
    {
        return $this->apiResource;
    }

    public function addStreamToBroadcast(string $broadcastId, string $streamId, bool $hasVideo = true, bool $hasAudio = true): void
    {
        $this->apiResource->partiallyUpdate(
            'v2/project/' . $this->credentials->application . '/broadcast/' . $broadcastId . '/streams',
            [
                'addStream' => $streamId,
                'hasAudio' => $hasAudio,
                'hasVideo' => $hasVideo,
            ],
        );
    }

    public function changeBroadcastLayout(string $broadcastId, Layout $layout): void
    {
        $this->apiResource->create(
            $layout->toArray(),
            'v2/project/' . $this->credentials->application . '/broadcast/' . $broadcastId . '/layout',
        );
    }

    public function createSession(?SessionOptions $options = null): Session
    {
        if (!$options) {
            $options = new SessionOptions();
        }

        $data = [
            'p2p.preference' => $options->getMediaMode(),
            'archiveMode' => $options->getArchiveMode(),
        ];

        if ($options->getLocation()) {
            $data['location'] = $options->getLocation();
        }

        if ($options->getE2ee()) {
            $data['e2ee'] = $options->getE2ee();
        }
        
        $response = $this->apiResource->submit(
            $data,
            '/session/create',
            [
                'Accept' => 'application/json',
                'content-type' => 'application/x-www-form-urlencoded', // Hack because APIResource does not create form type with other headers
            ]
        );

        $responseData = json_decode($response, true);
        $responseData = array_merge($responseData[0], $data);

        $session = new Session();
        $session->fromArray($responseData);

        return $session;
    }

    public function deleteArchive(string $archiveId): void
    {
        $this->apiResource->delete('v2/project/' . $this->credentials->application . '/archive/' . $archiveId);
    }

    /**
     * @param string[] $excludedStreams
     */
    public function disableForceMute(string $sessionId): ProjectDetails
    {
        $response = $this->apiResource->create(
            [
                'active' => false,
            ],
            '/v2/project/' . $this->credentials->application . '/session/' . $sessionId . '/mute'
        );
        return new ProjectDetails($response);
    }

    public function disconnectClient(string $sessionId, string $connectionId): void
    {
        $this->apiResource->delete('v2/project/' . $this->credentials->application . '/session/' . $sessionId . '/connection/' . $connectionId);
    }

    protected function extractCredentials(string $class, CredentialsInterface $credentials): CredentialsInterface
    {
        if ($credentials instanceof $class) {
            return $credentials;
        }

        if ($credentials instanceof Container) {
            $creds = $credentials->get($class);
            if (!is_null($creds)) {
                return $creds;
            }
        }

        throw new \RuntimeException('Requested auth type not found');
    }

    /**
     * @param string[] $excludedStreams
     */
    public function forceMuteAll(string $sessionId, array $excludedStreamIds = []): ProjectDetails
    {
        $response = $this->getAPIResource()->create(
            [
                'active' => true,
                'excludedStreamIds' => $excludedStreamIds
            ],
            '/v2/project/' . $this->credentials->application . '/session/' . $sessionId . '/mute'
        );
        return new ProjectDetails($response);
    }

    public function forceMuteStream(string $sessionId, string $streamId): ProjectDetails
    {
        $response = $this->getAPIResource()->create([], '/v2/project/' . $this->credentials->application . '/session/' . $sessionId . '/stream/' . $streamId . '/mute');
        return new ProjectDetails($response);
    }

    /**
     * @param array<string, mixed> $options 
     */
    public function generateClientToken(string $sessionId, array $options = []): string
    {
        $defaults = [
            'scope' => 'session.connect',
            'session_id' => $sessionId,
            'sub' => 'video',
            'acl' => [
                'paths' => [
                    '/session/**' => (object) [],
                ],
            ],
        ];

        $options = array_merge($defaults, $options);

        return TokenGenerator::factory($this->credentials->application, $this->credentials->key, $options);
    }

    public function getArchive(string $archiveId): Archive
    {
        $response = $this->getAPIResource()->get('v2/project/' . $this->credentials->application . '/archive/' . $archiveId);

        return new Archive($response);
    }

    public function getStream(string $sessionId, string $streamId)
    {
        $response = $this->getAPIResource()->get('v2/project/' . $this->credentials->application . '/session/' . $sessionId . '/stream/' . $streamId);

        return new Stream($response);
    }
    
    public function getBroadcast(string $broadcastId): Broadcast
    {
        $response = $this->getAPIResource()->get(
            'v2/project/' . $this->credentials->application . '/broadcast/' . $broadcastId
        );

        return new Broadcast($response);
    }

    /**
     * @return array{id: string, connectionId: string, streamId: string}
     */
    public function initiateOutboundSIPCall(string $sessionId, string $token, OutboundSIPConfig $config): array
    {
        $config = $config->toArray();
        $config['sessionId'] = $sessionId;
        $config['token'] = $token;

        $response = $this->getAPIResource()->create(
            $config,
            'v2/project/' . $this->credentials->application . '/dial'
        );
        
        return $response;
    }

    public function listArchives(FilterInterface $filter = null): IterableAPICollection
    {
        $response = $this->apiResource->search(
            $filter,
            '/v2/project/' . $this->credentials->application . '/archive'
        );
        $response->getApiResource()->setBaseUri('/v2/project/' . $this->credentials->application . '/archive');

        $hydrator = new ConstructorHydrator();
        $hydrator->setPrototype(Archive::class);
        $response->setHydrator($hydrator);
        $response->setNaiveCount(true);
        $response->getApiResource()->setCollectionName('items');

        return $response;
    }

    public function listStreams(string $sessionId): IterableAPICollection
    {
        $response = $this->apiResource->search(
            null,
            '/v2/project/' . $this->credentials->application . '/session/' . $sessionId . '/stream',
        );
        $response->getApiResource()->setBaseUri('/v2/project/' . $this->credentials->application . '/session/' . $sessionId . '/stream');

        $hydrator = new ConstructorHydrator();
        $hydrator->setPrototype(Stream::class);
        $response->setHydrator($hydrator);
        $response->setNaiveCount(true);

        return $response;
    }
    
    public function listBroadcasts(FilterInterface $filter = null): IterableAPICollection
    {
        $response = $this->apiResource->search(
            $filter,
            '/v2/project/' . $this->credentials->application . '/broadcast'
        );
        $response->getApiResource()->setBaseUri('/v2/project/' . $this->credentials->application . '/broadcast');

        $hydrator = new ConstructorHydrator();
        $hydrator->setPrototype(Broadcast::class);
        $response->setHydrator($hydrator);
        $response->getApiResource()->setCollectionName('items');

        return $response;
    }

    public function playDTMFIntoCall(string $sessionId, string $digits, ?string $connectionId): void
    {
        $uri = 'v2/project/' . $this->credentials->application . '/session/' . $sessionId . '/play-dtmf';
        if ($connectionId) {
            $uri = 'v2/project/' . $this->credentials->application . '/session/' . $sessionId . '/connection/' . $connectionId .'/play-dtmf';
        }

        $this->getAPIResource()->create(
            ['digits' => $digits],
            $uri
        );        
    }

    public function removeStreamFromBroadcast(string $broadcastId, string $streamId): void
    {
        $this->getAPIResource()->update(
            'v2/project/' . $this->credentials->application . '/broadcast/' . $broadcastId . '/streams',
            ['removeStream' => $streamId],
        );
    }

    public function sendSignal(string $sessionId, string $type, string $data, string $connectionId = null): void
    {
        $url = '/v2/project/' . $this->credentials->application . '/session/' . $sessionId . '/signal';
        if ($connectionId) {
            $url = '/v2/project/' . $this->credentials->application . '/session/' . $sessionId . '/connection/' . $connectionId . '/signal';
        }

        $this->getAPIResource()->create(
            ['type' => $type, 'data' => $data],
            $url
        );
    }

    public function startArchive(ArchiveConfig $archiveConfig): Archive
    {
        $response = $this->getAPIResource()->create(
            $archiveConfig->toArray(),
            '/v2/project/' . $this->credentials->application . '/archive'
        );

        return new Archive($response);
    }

    public function startBroadcast(BroadcastConfig $config)
    {
        $response = $this->getAPIResource()->create(
            $config->toArray(),
            '/v2/project/' . $this->credentials->application . '/broadcast'
        );

        return new Broadcast($response);
    }

    public function stopArchive(string $archiveId): Archive
    {
        $response = $this->getAPIResource()->create(
            [],
            '/v2/project/' . $this->credentials->application . '/archive/' . $archiveId . '/stop'
        );

        return new Archive($response);
    }

    public function updateArchiveLayout(string $archiveId, ArchiveLayout $layout): void
    {
        $this->apiResource->update(
            '/v2/project/' . $this->credentials->application . '/archive/' . $archiveId . '/layout',
            $layout->toArray(),
        );
    }

    public function stopBroadcast(string $broadcastId): Broadcast
    {
        $response = $this->getAPIResource()->create(
            [],
            '/v2/project/' . $this->credentials->application . '/broadcast/' . $broadcastId . '/stop'
        );

        return new Broadcast($response);
    }

    public function startExperienceComposerSession(
        string $sessionId,
        string $token,
        string $url,
        array $properties,
        string $resolution = '1280x720',
        int $maxDuration = 7200,
    ): Render
    {
        $payload = [
            'sessionId' => $sessionId,
            'token' => $token,
            'url' => $url,
            'properties' => $properties,
            'resolution' => $resolution,
            'maxDuration' => $maxDuration,
        ];

        $response = $this->getAPIResource()->create(
            $payload,
            '/v2/project/' . $this->credentials->application . '/render'
        );

        return new Render($response);
    }

    public function getExperienceComposerSession(string $sessionId): Render
    {
        $response = $this->getAPIResource()->get('v2/project/' . $this->credentials->application . '/render/' . $sessionId);
        return new Render($response);
    }

    public function stopExperienceComposerSession(string $sessionId): bool
    {
        try {
            $response = $this->getAPIResource()->delete('v2/project/' . $this->credentials->application . '/render/' . $sessionId);
        } catch (Request $e) {
            return false;
        }

        return true;
    }

    public function listExperienceComposerSessions(?EmptyFilter $filter): IterableAPICollection
    {
        if (is_null($filter)) {
            $filter = new EmptyFilter();
        }

        /** @var IterableAPICollection $iterator */
        $iterator = $this->getAPIResource()->search($filter, '/v2/project/' . $this->credentials->application . '/render');
        $iterator->setNaiveCount(true);
        $hydrator = new ConstructorHydrator();
        $hydrator->setPrototype(Render::class);
        $iterator->setHydrator($hydrator);

        return $iterator;
    }

    public function connectAudio(string $sessionId, string $token, WebsocketOptions $websocketOptions): ?array
    {
        if (is_null($websocketOptions->getUri())) {
            throw new \InvalidArgumentException('Websocket option "uri" is required.');
        }

        $payload = [
            'sessionId' => $sessionId,
            'token' => $token,
            'websocket' => $websocketOptions->toArray(),
        ];

        return $this->getAPIResource()->create($payload, '/v2/project/' . $this->credentials->application . '/connect');
    }

    public function startCaptions(string $sessionId, string $token, ?CaptionOptions $captionOptions = null): ?array
    {
        $payload = [
            'sessionId' => $sessionId,
            'token' => $token
        ];

        if (!is_null($captionOptions)) {
            $payload = array_merge($payload, $captionOptions->toArray());
        }

        return $this->getAPIResource()->create($payload, '/v2/project/' . $this->credentials->application . '/captions');
    }

    public function stopCaptions(string $captionsId): bool
    {
        try {
            $this->getAPIResource()->create([], '/v2/project/' . $this->credentials->application . '/captions/' . $captionsId . '/stop');
        } catch (Request $e) {
            return false;
        }

        return true;
    }
}
