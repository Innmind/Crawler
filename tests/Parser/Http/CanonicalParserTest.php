<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    ParserInterface,
    Parser\Http\CanonicalParser,
    HttpResource\AttributeInterface
};
use Innmind\UrlResolver\ResolverInterface;
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PointInTimeInterface,
    ElapsedPeriod
};
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
use Innmind\Url\Url;
use Innmind\Immutable\{
    Map,
    Set
};

class CanonicalParserTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            ParserInterface::class,
            new CanonicalParser(
                $this->createMock(ResolverInterface::class),
                $this->createMock(TimeContinuumInterface::class)
            )
        );
    }

    public function testDoesntParseWhenNoLink()
    {
        $parser = new CanonicalParser(
            $this->createMock(ResolverInterface::class),
            $this->createMock(TimeContinuumInterface::class)
        );
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

        $attributes = $parser->parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenLinkNotFullyParsed()
    {
        $parser = new CanonicalParser(
            $this->createMock(ResolverInterface::class),
            $this->createMock(TimeContinuumInterface::class)
        );
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

        $attributes = $parser->parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenCanonicalLinkNotFound()
    {
        $parser = new CanonicalParser(
            $this->createMock(ResolverInterface::class),
            $this->createMock(TimeContinuumInterface::class)
        );
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

        $attributes = $parser->parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenMultipleCanonicalLinksFound()
    {
        $parser = new CanonicalParser(
            $this->createMock(ResolverInterface::class),
            $this->createMock(TimeContinuumInterface::class)
        );
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

        $attributes = $parser->parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $parser = new CanonicalParser(
            $resolver = $this->createMock(ResolverInterface::class),
            $clock = $this->createMock(TimeContinuumInterface::class)
        );
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
        $clock
            ->expects($this->exactly(2))
            ->method('now')
            ->will(
                $this->onConsecutiveCalls(
                    $start = $this->createMock(PointInTimeInterface::class),
                    $end = $this->createMock(PointInTimeInterface::class)
                )
            );
        $end
            ->expects($this->once())
            ->method('elapsedSince')
            ->with($start)
            ->willReturn(new ElapsedPeriod(42));
        $request = $this->createMock(RequestInterface::class);
        $request
            ->expects($this->once())
            ->method('url')
            ->willReturn(Url::fromString('http://example.com/whatever'));
        $resolver
            ->expects($this->once())
            ->method('resolve')
            ->with(
                'http://example.com/whatever',
                '/foo'
            )
            ->willReturn('http://example.com/foo');
        $expected = new Map('string', AttributeInterface::class);

        $attributes = $parser->parse($request, $response, $expected);

        $this->assertNotSame($expected, $attributes);
        $this->assertCount(1, $attributes);
        $this->assertSame('canonical', $attributes->key());
        $this->assertSame('canonical', $attributes->current()->name());
        $this->assertSame(
            'http://example.com/foo',
            $attributes->current()->content()
        );
        $this->assertSame(42, $attributes->current()->parsingTime());
    }
}
