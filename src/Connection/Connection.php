<?php
declare(strict_types=1);

namespace Vonage\Video\Connection;

use DateTimeImmutable;

class Connection
{
    readonly string $connectionId;
    readonly \DateTimeImmutable $createdAt;
    readonly ConnectionState $connectionState;

    public function __construct(array $data)
    {
        $this->connectionId = $data['connectionId'];
        $this->createdAt = new \DateTimeImmutable('@' . $data['createdAt']);
        $this->connectionState = ConnectionState::from($data['connectionState']);
    }

    /**
     * @deprecated Use connectionId property directly
     * @return string 
     */
    public function getConnectionId(): string
    {
        return $this->connectionId;
    }

    /**
     * @deprecated Use createdAt property directly
     * @return DateTimeImmutable 
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @deprecated Use connectionState property directly
     * @return ConnectionState 
     */
    public function getConnectionState(): ConnectionState
    {
        return $this->connectionState;
    }

    /**
     * @return array{connectionId: string, createdAt: string, connectionState: string} 
     */
    public function toArray(): array
    {
        return [
            'connectionId' => $this->connectionId,
            'createdAt' => $this->createdAt->format('c'),
            'connectionState' => $this->connectionState->value,
        ];
    }
}