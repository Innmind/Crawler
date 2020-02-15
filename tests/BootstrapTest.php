<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler;

use function Innmind\Crawler\bootstrap;
use Innmind\Crawler\Crawler\Crawler;
use Innmind\HttpTransport\Transport;
use Innmind\TimeContinuum\Clock;
use Innmind\Xml\Reader;
use Innmind\UrlResolver\Resolver;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $crawler = bootstrap(
            $this->createMock(Transport::class),
            $this->createMock(Clock::class),
            $this->createMock(Reader::class),
            $this->createMock(Resolver::class)
        );

        $this->assertInstanceOf(Crawler::class, $crawler);
    }
}
