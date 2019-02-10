<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler;

use function Innmind\Crawler\bootstrap;
use Innmind\Crawler\Crawler\Crawler;
use Innmind\HttpTransport\Transport;
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Xml\Reader;
use Innmind\UrlResolver\ResolverInterface;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $crawler = bootstrap(
            $this->createMock(Transport::class),
            $this->createMock(TimeContinuumInterface::class),
            $this->createMock(Reader::class),
            $this->createMock(ResolverInterface::class)
        );

        $this->assertInstanceOf(Crawler::class, $crawler);
    }
}
