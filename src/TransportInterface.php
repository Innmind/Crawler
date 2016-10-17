<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
};

interface TransportInterface
{
    public function fulfill(RequestInterface $request): ResponseInterface;
}
