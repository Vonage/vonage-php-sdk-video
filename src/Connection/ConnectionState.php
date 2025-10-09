<?php
declare(strict_types=1);

namespace Vonage\Video\Connection;

enum ConnectionState: string
{
    case Connecting = 'Connecting';
    case Connected = 'Connected';
    case Disconnected = 'Disconnected';
}