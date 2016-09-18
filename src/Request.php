<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Crawler\Exception\InvalidArgumentException;
use Innmind\Url\UrlInterface;
use Innmind\Http\Message\Method;
use Innmind\Filesystem\StreamInterface;
use Innmind\Immutable\MapInterface;

final class Request
{
    private $url;
    private $headers;
    private $method;
    private $payload;

    public function __construct(UrlInterface $url)
    {
        if (empty((string) $url->authority()->host())) {
            throw new InvalidArgumentException;
        }

        $this->url = $url;
        $this->method = new Method('GET');
    }

    public function url(): UrlInterface
    {
        return $this->url;
    }

    public function withHeaders(MapInterface $headers): self
    {
        if (
            (string) $headers->keyType() !== 'string' ||
            (string) $headers->valueType() !== 'string'
        ) {
            throw new InvalidArgumentException;
        }

        $request = clone $this;
        $request->headers = $headers;

        return $request;
    }

    public function hasHeaders(): bool
    {
        return $this->headers instanceof MapInterface;
    }

    public function headers(): MapInterface
    {
        return $this->headers;
    }

    public function withMethod(Method $method): self
    {
        $request = clone $this;
        $request->method = $method;

        return $request;
    }

    public function method(): Method
    {
        return $this->method;
    }

    public function withPayload(StreamInterface $payload): self
    {
        $request = clone $this;
        $request->payload = $payload;

        return $request;
    }

    public function hasPayload(): bool
    {
        return $this->payload instanceof StreamInterface;
    }

    public function payload(): StreamInterface
    {
        return $this->payload;
    }
}
