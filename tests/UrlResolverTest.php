<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler;

use Innmind\Crawler\{
    UrlResolver,
    HttpResource\Attribute,
    Parser\Html\BaseParser,
};
use Innmind\UrlResolver\UrlResolver as BaseResolver;
use Innmind\Http\Message\Request;
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class UrlResolverTest extends TestCase
{
    public function testResolveFromRequestUrl()
    {
        $resolver = new UrlResolver(new BaseResolver);
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('url')
            ->willReturn(Url::fromString('http://github.com/Innmind/'));

        $url = $resolver->resolve(
            $request,
            new Map('string', Attribute::class),
            Url::fromString('/foo')
        );

        $this->assertInstanceOf(UrlInterface::class, $url);
        $this->assertSame('http://github.com/foo', (string) $url);
    }

    public function testResolveFromBase()
    {
        $resolver = new UrlResolver(new BaseResolver);
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('url')
            ->willReturn(Url::fromString('http://github.com/Innmind/'));

        $url = $resolver->resolve(
            $request,
            (new Map('string', Attribute::class))
                ->put(
                    BaseParser::key(),
                    new Attribute\Attribute(
                        BaseParser::key(),
                        Url::fromString('http://sub.github.com'),
                        0
                    )
                ),
            Url::fromString('/foo')
        );

        $this->assertInstanceOf(UrlInterface::class, $url);
        $this->assertSame('http://sub.github.com/foo', (string) $url);
    }
}
