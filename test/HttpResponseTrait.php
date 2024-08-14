<?php

declare(strict_types=1);

namespace VonageTest;

use Laminas\Diactoros\Response;

trait HttpResponseTrait
{
    protected string $responseDir = __DIR__ . '/responses/';

    public function setResponseDir(string $responseDir): self
    {
        $this->responseDir = $responseDir;

        return $this;
    }

    public function getResponseDir(): string
    {
        return $this->responseDir;
    }

    protected function getResponse(string $type = 'success', int $status = 200): Response
    {
        return new Response(fopen($this->getResponseDir() . $type . '.json', 'rb'), $status);
    }
}