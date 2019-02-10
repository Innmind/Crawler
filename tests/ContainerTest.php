<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler;

use Innmind\Crawler\Crawler\Crawler;
use Innmind\Compose\{
    ContainerBuilder\ContainerBuilder,
    Loader\Yaml
};
use Innmind\Url\Path;
use Innmind\Immutable\Map;
use Innmind\HttpTransport\Transport;
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Xml\Reader;
use Innmind\UrlResolver\ResolverInterface;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testService()
    {
        $container = (new ContainerBuilder(new Yaml))(
            new Path('container.yml'),
            (new Map('string', 'mixed'))
                ->put('transport', $this->createMock(Transport::class))
                ->put('clock', $this->createMock(TimeContinuumInterface::class))
                ->put('reader', $this->createMock(Reader::class))
                ->put('urlResolver', $this->createMock(ResolverInterface::class))
        );

        $this->assertInstanceOf(Crawler::class, $container->get('crawler'));
    }
}
