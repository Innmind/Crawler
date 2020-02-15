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
use Innmind\Url\Url;
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
                Headers::of(),
            );
        $request = $this->createMock(Request::class);
        $expected = Map::of('string', Attribute::class);

        $attributes = ($this->parse)($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenLinkNotFullyParsed()
    {
        $response = $this->createMock(Response::class);
        $headers = Headers::of(
            new Header\Header('Link'),
        );
        $response
            ->expects($this->exactly(2))
            ->method('headers')
            ->willReturn($headers);
        $request = $this->createMock(Request::class);
        $expected = Map::of('string', Attribute::class);

        $attributes = ($this->parse)($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenCanonicalLinkNotFound()
    {
        $response = $this->createMock(Response::class);
        $headers = Headers::of(
            new Link(
                new LinkValue(
                    Url::of('/'),
                    'foo'
                )
            )
        );
        $response
            ->expects($this->exactly(3))
            ->method('headers')
            ->willReturn($headers);
        $request = $this->createMock(Request::class);
        $expected = Map::of('string', Attribute::class);

        $attributes = ($this->parse)($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenMultipleCanonicalLinksFound()
    {
        $response = $this->createMock(Response::class);
        $headers = Headers::of(
            new Link(
                new LinkValue(
                    Url::of('/'),
                    'foo'
                ),
                new LinkValue(
                    Url::of('/foo'),
                    'canonical'
                ),
                new LinkValue(
                    Url::of('/bar'),
                    'canonical'
                )
            )
        );
        $response
            ->expects($this->exactly(3))
            ->method('headers')
            ->willReturn($headers);
        $request = $this->createMock(Request::class);
        $expected = Map::of('string', Attribute::class);

        $attributes = ($this->parse)($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $response = $this->createMock(Response::class);
        $headers = Headers::of(
            new Link(
                new LinkValue(
                    Url::of('/'),
                    'foo'
                ),
                new LinkValue(
                    Url::of('/foo'),
                    'canonical'
                )
            )
        );
        $response
            ->expects($this->exactly(3))
            ->method('headers')
            ->willReturn($headers);
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('url')
            ->willReturn(Url::of('http://example.com/whatever'));
        $expected = Map::of('string', Attribute::class);

        $attributes = ($this->parse)($request, $response, $expected);

        $this->assertNotSame($expected, $attributes);
        $this->assertCount(1, $attributes);
        $this->assertTrue($attributes->contains('canonical'));
        $this->assertSame('canonical', $attributes->get('canonical')->name());
        $this->assertInstanceOf(
            Url::class,
            $attributes->get('canonical')->content()
        );
        $this->assertSame(
            'http://example.com/foo',
            $attributes->get('canonical')->content()->toString(),
        );
    }
}
