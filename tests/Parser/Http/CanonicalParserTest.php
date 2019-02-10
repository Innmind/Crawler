<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser,
    Parser\Http\CanonicalParser,
    HttpResource\Attribute,
    UrlResolver,
};
use Innmind\UrlResolver\UrlResolver as BaseResolver;
use Innmind\Http\{
    Message\Response,
    Message\Request,
    Headers,
    Header,
    Header\Link,
    Header\LinkValue,
};
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class CanonicalParserTest extends TestCase
{
    private $parse;

    public function setUp(): void
    {
        $this->parse = new CanonicalParser(
            new UrlResolver(new BaseResolver)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Parser::class,
            $this->parse
        );
    }

    public function testKey()
    {
        $this->assertSame('canonical', CanonicalParser::key());
    }

    public function testDoesntParseWhenNoLink()
    {
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                $headers = $this->createMock(Headers::class)
            );
        $headers
            ->expects($this->once())
            ->method('has')
            ->with('Link')
            ->willReturn(false);
        $request = $this->createMock(Request::class);
        $expected = new Map('string', Attribute::class);

        $attributes = ($this->parse)($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenLinkNotFullyParsed()
    {
        $response = $this->createMock(Response::class);
        $headers = $this->createMock(Headers::class);
        $response
            ->expects($this->exactly(2))
            ->method('headers')
            ->willReturn($headers);
        $headers
            ->expects($this->once())
            ->method('has')
            ->with('Link')
            ->willReturn(true);
        $headers
            ->expects($this->once())
            ->method('get')
            ->with('Link')
            ->willReturn($this->createMock(Header::class));
        $request = $this->createMock(Request::class);
        $expected = new Map('string', Attribute::class);

        $attributes = ($this->parse)($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenCanonicalLinkNotFound()
    {
        $response = $this->createMock(Response::class);
        $headers = $this->createMock(Headers::class);
        $response
            ->expects($this->exactly(3))
            ->method('headers')
            ->willReturn($headers);
        $headers
            ->expects($this->once())
            ->method('has')
            ->with('Link')
            ->willReturn(true);
        $headers
            ->expects($this->exactly(2))
            ->method('get')
            ->with('Link')
            ->willReturn(
                new Link(
                    new LinkValue(
                        Url::fromString('/'),
                        'foo'
                    )
                )
            );
        $request = $this->createMock(Request::class);
        $expected = new Map('string', Attribute::class);

        $attributes = ($this->parse)($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenMultipleCanonicalLinksFound()
    {
        $response = $this->createMock(Response::class);
        $headers = $this->createMock(Headers::class);
        $response
            ->expects($this->exactly(3))
            ->method('headers')
            ->willReturn($headers);
        $headers
            ->expects($this->once())
            ->method('has')
            ->with('Link')
            ->willReturn(true);
        $headers
            ->expects($this->exactly(2))
            ->method('get')
            ->with('Link')
            ->willReturn(
                new Link(
                    new LinkValue(
                        Url::fromString('/'),
                        'foo'
                    ),
                    new LinkValue(
                        Url::fromString('/foo'),
                        'canonical'
                    ),
                    new LinkValue(
                        Url::fromString('/bar'),
                        'canonical'
                    )
                )
            );
        $request = $this->createMock(Request::class);
        $expected = new Map('string', Attribute::class);

        $attributes = ($this->parse)($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $response = $this->createMock(Response::class);
        $headers = $this->createMock(Headers::class);
        $response
            ->expects($this->exactly(3))
            ->method('headers')
            ->willReturn($headers);
        $headers
            ->expects($this->once())
            ->method('has')
            ->with('Link')
            ->willReturn(true);
        $headers
            ->expects($this->exactly(2))
            ->method('get')
            ->with('Link')
            ->willReturn(
                new Link(
                    new LinkValue(
                        Url::fromString('/'),
                        'foo'
                    ),
                    new LinkValue(
                        Url::fromString('/foo'),
                        'canonical'
                    )
                )
            );
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('url')
            ->willReturn(Url::fromString('http://example.com/whatever'));
        $expected = new Map('string', Attribute::class);

        $attributes = ($this->parse)($request, $response, $expected);

        $this->assertNotSame($expected, $attributes);
        $this->assertCount(1, $attributes);
        $this->assertSame('canonical', $attributes->key());
        $this->assertSame('canonical', $attributes->current()->name());
        $this->assertInstanceOf(
            UrlInterface::class,
            $attributes->current()->content()
        );
        $this->assertSame(
            'http://example.com/foo',
            (string) $attributes->current()->content()
        );
    }
}
