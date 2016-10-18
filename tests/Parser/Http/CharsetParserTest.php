<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    ParserInterface,
    Parser\Http\CharsetParser,
    HttpResource\AttributeInterface
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PointInTimeInterface,
    ElapsedPeriod
};
use Innmind\Http\{
    Message\RequestInterface,
    Message\ResponseInterface,
    HeadersInterface,
    Header\HeaderInterface,
    Header\ContentType,
    Header\ContentTypeValue,
    Header\ParameterInterface,
    Header\Parameter
};
use Innmind\Immutable\Map;

class CharsetParserTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            ParserInterface::class,
            new CharsetParser($this->createMock(TimeContinuumInterface::class))
        );
    }

    public function testKey()
    {
        $this->assertSame('charset', CharsetParser::key());
    }

    public function testDoesntParseWhenNoContentType()
    {
        $parser = new CharsetParser(
            $this->createMock(TimeContinuumInterface::class)
        );
        $request = $this->createMock(RequestInterface::class);
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
            ->with('Content-Type')
            ->willReturn(false);
        $expected = new Map('string', AttributeInterface::class);

        $attributes = $parser->parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenContentTypeNotFullyParsed()
    {
        $parser = new CharsetParser(
            $this->createMock(TimeContinuumInterface::class)
        );
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('headers')
            ->willReturn(
                $headers = $this->createMock(HeadersInterface::class)
            );
        $headers
            ->expects($this->once())
            ->method('has')
            ->with('Content-Type')
            ->willReturn(true);
        $headers
            ->expects($this->once())
            ->method('get')
            ->with('Content-Type')
            ->willReturn($this->createMock(HeaderInterface::class));
        $expected = new Map('string', AttributeInterface::class);

        $attributes = $parser->parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenNoCharset()
    {
        $parser = new CharsetParser(
            $this->createMock(TimeContinuumInterface::class)
        );
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('headers')
            ->willReturn(
                $headers = $this->createMock(HeadersInterface::class)
            );
        $headers
            ->expects($this->once())
            ->method('has')
            ->with('Content-Type')
            ->willReturn(true);
        $headers
            ->expects($this->once())
            ->method('get')
            ->with('Content-Type')
            ->willReturn(
                new ContentType(
                    new ContentTypeValue(
                        'foo',
                        'bar',
                        new Map('string', ParameterInterface::class)
                    )
                )
            );
        $expected = new Map('string', AttributeInterface::class);

        $attributes = $parser->parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $parser = new CharsetParser(
            $clock = $this->createMock(TimeContinuumInterface::class)
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
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('headers')
            ->willReturn(
                $headers = $this->createMock(HeadersInterface::class)
            );
        $headers
            ->expects($this->once())
            ->method('has')
            ->with('Content-Type')
            ->willReturn(true);
        $headers
            ->expects($this->once())
            ->method('get')
            ->with('Content-Type')
            ->willReturn(
                new ContentType(
                    new ContentTypeValue(
                        'foo',
                        'bar',
                        (new Map('string', ParameterInterface::class))
                            ->put(
                                'charset',
                                new Parameter('charset', 'utf-8')
                            )
                    )
                )
            );
        $expected = new Map('string', AttributeInterface::class);

        $attributes = $parser->parse($request, $response, $expected);

        $this->assertNotSame($expected, $attributes);
        $this->assertCount(1, $attributes);
        $this->assertSame('charset', $attributes->key());
        $this->assertSame('charset', $attributes->current()->name());
        $this->assertSame('utf-8', $attributes->current()->content());
        $this->assertSame(42, $attributes->current()->parsingTime());
    }
}
