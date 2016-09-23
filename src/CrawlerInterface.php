<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Http\Message\RequestInterface;

interface CrawlerInterface
{
    public function execute(RequestInterface $request): HttpResource;
}
