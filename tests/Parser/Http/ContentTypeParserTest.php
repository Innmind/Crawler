<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    ParserInterface,
    Parser\Http\ContentTypeParser,
    HttpResource\AttributeInterface
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PointInTimeInterface,
    ElapsedPeriod
};
use Innmind\Filesystem\MediaTypeInterface;
use Innmind\Http\{
    Message\RequestInterface,
    Message\ResponseInterface,
    HeadersInterface,
    Header\HeaderInterface,
    Header\ContentType,
    Header\ContentTypeValue,
    Header\ParameterInterface
};
use Innmind\Immutable\Map;

class ContentTypeParserTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            ParserInterface::class,
            new ContentTypeParser(
                $this->createMock(TimeContinuumInterface::class)
            )
        );
    }

    public function testDoesntParseWhenNoContentType()
    {
        $parser = new ContentTypeParser(
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
        $parser = new ContentTypeParser(
            $this->createMock(TimeContinuumInterface::class)
        );
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $headers = $this->createMock(HeadersInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('headers')
            ->willReturn($headers);
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

    public function testParse()
    {
        $parser = new ContentTypeParser(
            $clock = $this->createMock(TimeContinuumInterface::class)
        );
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $headers = $this->createMock(HeadersInterface::class);
        $response
            ->expects($this->exactly(3))
            ->method('headers')
            ->willReturn($headers);
        $headers
            ->expects($this->once())
            ->method('has')
            ->with('Content-Type')
            ->willReturn(true);
        $headers
            ->expects($this->exactly(2))
            ->method('get')
            ->with('Content-Type')
            ->willReturn(
                new ContentType(
                    new ContentTypeValue(
                        'text',
                        'bar',
                        new Map('string', ParameterInterface::class)
                    )
                )
            );
        $expected = new Map('string', AttributeInterface::class);
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

        $attributes = $parser->parse($request, $response, $expected);

        $this->assertNotSame($expected, $attributes);
        $this->assertCount(1, $attributes);
        $this->assertSame('content_type', $attributes->key());
        $this->assertSame('content_type', $attributes->current()->name());
        $this->assertInstanceOf(
            MediaTypeInterface::class,
            $attributes->current()->content()
        );
        $this->assertSame('text/bar', (string) $attributes->current()->content());
        $this->assertSame(42, $attributes->current()->parsingTime());
    }
}
