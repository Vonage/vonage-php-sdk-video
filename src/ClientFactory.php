<?php
declare(strict_types=1);

namespace Vonage\Video;

use Vonage\Client\APIResource;
use Vonage\Client\Credentials\Handler\KeypairHandler;
use Vonage\Client\Factory\MapFactory;
use Vonage\Video\Entity\IterableAPICollection;

class ClientFactory
{
    public function __invoke(MapFactory $containerInterface): Client
    {
        $baseUrl = getenv('VONAGE_VIDEO_API_SERVER_URL') ?: 'https://video.api.vonage.com';
        /** @var APIResource $apiResource */
        $apiResource = $containerInterface->make(APIResource::class);
        $apiResource->setBaseUrl($baseUrl);
        $apiResource->setIsHAL(false);
        $apiResource->setAuthHandlers(new KeypairHandler());
        $apiResource->setCollectionPrototype(new IterableAPICollection());
        $apiResource->setCollectionName('items');

        return new Client($apiResource);
    }
}
