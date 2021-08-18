# Vonage PHP SDK for Video

<img src="https://developer.nexmo.com/assets/images/Vonage_Nexmo.svg" height="48px" alt="Nexmo is now known as Vonage" />

This is a PHP client library for the Vonage Video API. It extends the
[Vonage PHP library](https://raw.githubusercontent.com/Vonage/vonage-php-sdk-core).

Usage
-----

If you're using Composer, make sure the autoloader is included in your project's bootstrap file:

```php
require_once "vendor/autoload.php";
```

Create a client with your Vonage application ID and private key:

```php
use Vonage\Client;
use Vonage\Client\Credentials\Keypair;
use Vonage\Video\ClientFactory;

$credentials = new Keypair('private-key-string', 'application-id');
$client = new Client($credentials);
$client->getFactory()->set('video', new ClientFactory());
$vonageVideoClient = $client->get(Client::class);
```

For testing purposes you can change the API URL that the client makes requests to
from `https://anvil-tbdev.opentok.com` to something else. To do this, set the
`VONAGE_VIDEO_API_URL` environment variable:

```
export VONAGE_VIDEO_API_URL=https://api-eu.qa.v1.vonagenetworks.net/video
```
