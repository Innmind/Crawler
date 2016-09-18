<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Http\Message\ResponseInterface;

interface TransportInterface
{
    public function apply(Request $request): ResponseInterface;
}
