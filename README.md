# Vonage PHP SDK for Video

[![Contributor Covenant](https://img.shields.io/badge/Contributor%20Covenant-v2.0%20adopted-ff69b4.svg)](CODE_OF_CONDUCT.md)
[![Build Status](https://github.com/vonage/vonage-php-sdk-videos/workflows/build/badge.svg)](https://github.com/Vonage/vonage-php-sdk-core/actions?query=workflow%3Abuild)
[![Latest Stable Version](https://poser.pugx.org/vonage/video/v/stable)](https://packagist.org/packages/vonage/video)
[![License](https://img.shields.io/badge/License-Apache_2.0-blue.svg)](https://opensource.org/licenses/Apache-2.0)
[![codecov](https://codecov.io/gh/Vonage/vonage-php-sdk-video/branch/0.x/graph/badge.svg)](https://codecov.io/gh/vonage/vonage-php-sdk-core)

![The Vonage logo](./vonage_logo.png)

*This library requires a minimum PHP version of 8.1*

This is a PHP client library for the Vonage Video API. It extends the
[Vonage PHP library](https://raw.githubusercontent.com/Vonage/vonage-php-sdk-core). To use this, you'll need a Vonage account. Sign up [for free at
nexmo.com][signup].

 * [Installation](#installation)
 * [Usage](#usage)
 * [Examples](#examples)
 * [Contributing](#contributing)

## Installation
-----

To use the client library you'll need to have [created a Vonage account][signup].

To install the PHP client library to your project, we recommend using [Composer](https://getcomposer.org/).

```bash
composer require vonage/video
```

> PLEASE NOTE that this package is not designed to be used as a standalone. It requires the `vonage/client-core` package to work, but you
> might not have a PSR-11-compliant HTTP Client installed which is required for this. In order to get around this, install
> `vonage/client-core` first, then `vonage/video`. This will cover all requirements of the package.

If you're new to Composer, here are some resources that you may find useful:

* [Composer's Getting Started page](https://getcomposer.org/doc/00-intro.md) from Composer project's documentation.
* [A Beginner's Guide to Composer](https://scotch.io/tutorials/a-beginners-guide-to-composer) from the good people at ScotchBox.

## Usage

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
$vonageVideoClient = $client->video();
```

For testing purposes you can change the API URL that the client makes requests to
from `https://video.api.vonage.com` to something else. To do this, pass the `base_video_url` option
when creating the Vonage client:

```php
$credentials = new Keypair('private-key-string', 'application-id');
$options = ['base_video_url' => 'https://local-testing.video.example.com'];
$client = new Client($credentials. $options);
```
## Examples
### Creating a new Session
```php
$session = $client->video()->createSession();
echo $session->getSessionId();
```

### Create a new session with an Archive
```php
use Vonage\Video;
use Vonage\Video\Archive\ArchiveMode;

$session = $client->video()->createSession(new SessionOptions(['archiveMode' => ArchiveMode::ALWAYS]));
echo $session->getSessionId();
```

### Generating a Client Token
```php
$token = $client->video()->generateClientToken($session->getSessionId());
```

## Supported APIs

The following is a list of Vonage Video APIs and whether the SDK provides support for them:

| API                       | Supported? |
|---------------------------|:----------:|
| Session Creation          |     ✅      |
| Signaling                 |     ✅      |
| Force Muting              |     ✅      |
| Archiving                 |     ✅      |
| Custom S3/Azure buckets   |     ❌      |
| SIP Interconnect          |     ❌      |
| Live Streaming Broadcasts |     ❌      |
| Experience Composer       |     ❌      |
| Account Management        |     ❌      |
