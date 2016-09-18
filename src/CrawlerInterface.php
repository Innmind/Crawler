<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

interface CrawlerInterface
{
    public function execute(Request $request): HttpResource;
}
