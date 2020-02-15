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
use Innmind\Url\Url;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class UrlResolverTest extends TestCase
{
    public function testResolveFromRequestUrl()
    {
        $resolve = new UrlResolver(new BaseResolver);
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('url')
            ->willReturn(Url::of('http://github.com/Innmind/'));

        $url = $resolve(
            $request,
            Map::of('string', Attribute::class),
            Url::of('/foo')
        );

        $this->assertInstanceOf(Url::class, $url);
        $this->assertSame('http://github.com/foo', $url->toString());
    }

    public function testResolveFromBase()
    {
        $resolve = new UrlResolver(new BaseResolver);
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('url')
            ->willReturn(Url::of('http://github.com/Innmind/'));

        $url = $resolve(
            $request,
            Map::of('string', Attribute::class)
                (
                    BaseParser::key(),
                    new Attribute\Attribute(
                        BaseParser::key(),
                        Url::of('http://sub.github.com'),
                        0
                    )
                ),
            Url::of('/foo')
        );

        $this->assertInstanceOf(Url::class, $url);
        $this->assertSame('http://sub.github.com/foo', $url->toString());
    }

    public function testResolveBaseFromRequestedUrlInCaseBaseIsRelativeToIt()
    {
        $resolve = new UrlResolver(new BaseResolver);
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('url')
            ->willReturn(Url::of('http://github.com/Innmind/'));

        $url = $resolve(
            $request,
            Map::of('string', Attribute::class)
                (
                    BaseParser::key(),
                    new Attribute\Attribute(
                        BaseParser::key(),
                        Url::of('/SomeRepo/'),
                        0
                    )
                ),
            Url::of('foo')
        );

        $this->assertInstanceOf(Url::class, $url);
        $this->assertSame('http://github.com/SomeRepo/foo', $url->toString());
    }
}
