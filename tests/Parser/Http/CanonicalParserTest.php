<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    ParserInterface,
    Parser\Http\CanonicalParser,
    HttpResource\AttributeInterface,
    UrlResolver
};
use Innmind\UrlResolver\UrlResolver as BaseResolver;
use Innmind\Http\{
    Message\ResponseInterface,
    Message\RequestInterface,
    HeadersInterface,
    Header\HeaderInterface,
    Header\HeaderValueInterface,
    Header\Link,
    Header\LinkValue,
    Header\ParameterInterface
};
use Innmind\Url\{
    Url,
    UrlInterface
};
use Innmind\Immutable\{
    Map,
    Set
};
use PHPUnit\Framework\TestCase;

class CanonicalParserTest extends TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new CanonicalParser(
            new UrlResolver(new BaseResolver)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            ParserInterface::class,
            $this->parser
        );
    }

    public function testKey()
    {
        $this->assertSame('canonical', CanonicalParser::key());
    }

    public function testDoesntParseWhenNoLink()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                $headers = $this->createMock(HeadersInterface::class)
            );
        $headers
            ->expects($this->once())
            ->method('has')
            ->with('Link')
            ->willReturn(false);
        $request = $this->createMock(RequestInterface::class);
        $expected = new Map('string', AttributeInterface::class);

        $attributes = $this->parser->parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenLinkNotFullyParsed()
    {
        $response = $this->createMock(ResponseInterface::class);
        $headers = $this->createMock(HeadersInterface::class);
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
            ->willReturn($this->createMock(HeaderInterface::class));
        $request = $this->createMock(RequestInterface::class);
        $expected = new Map('string', AttributeInterface::class);

        $attributes = $this->parser->parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenCanonicalLinkNotFound()
    {
        $response = $this->createMock(ResponseInterface::class);
        $headers = $this->createMock(HeadersInterface::class);
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
                    (new Set(HeaderValueInterface::class))
                        ->add(
                            new LinkValue(
                                Url::fromString('/'),
                                'foo',
                                new Map('string', ParameterInterface::class)
                            )
                        )
                )
            );
        $request = $this->createMock(RequestInterface::class);
        $expected = new Map('string', AttributeInterface::class);

        $attributes = $this->parser->parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenMultipleCanonicalLinksFound()
    {
        $response = $this->createMock(ResponseInterface::class);
        $headers = $this->createMock(HeadersInterface::class);
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
                    (new Set(HeaderValueInterface::class))
                        ->add(
                            new LinkValue(
                                Url::fromString('/'),
                                'foo',
                                new Map('string', ParameterInterface::class)
                            )
                        )
                        ->add(
                            new LinkValue(
                                Url::fromString('/foo'),
                                'canonical',
                                new Map('string', ParameterInterface::class)
                            )
                        )
                        ->add(
                            new LinkValue(
                                Url::fromString('/bar'),
                                'canonical',
                                new Map('string', ParameterInterface::class)
                            )
                        )
                )
            );
        $request = $this->createMock(RequestInterface::class);
        $expected = new Map('string', AttributeInterface::class);

        $attributes = $this->parser->parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $response = $this->createMock(ResponseInterface::class);
        $headers = $this->createMock(HeadersInterface::class);
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
                    (new Set(HeaderValueInterface::class))
                        ->add(
                            new LinkValue(
                                Url::fromString('/'),
                                'foo',
                                new Map('string', ParameterInterface::class)
                            )
                        )
                        ->add(
                            new LinkValue(
                                Url::fromString('/foo'),
                                'canonical',
                                new Map('string', ParameterInterface::class)
                            )
                        )
                )
            );
        $request = $this->createMock(RequestInterface::class);
        $request
            ->expects($this->once())
            ->method('url')
            ->willReturn(Url::fromString('http://example.com/whatever'));
        $expected = new Map('string', AttributeInterface::class);

        $attributes = $this->parser->parse($request, $response, $expected);

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
