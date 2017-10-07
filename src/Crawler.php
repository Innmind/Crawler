<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Http\Message\Request;

interface Crawler
{
    public function execute(Request $request): HttpResource;
}
