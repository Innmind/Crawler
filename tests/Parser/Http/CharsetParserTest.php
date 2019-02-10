<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser,
    Parser\Http\CharsetParser,
    HttpResource\Attribute,
};
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Headers,
    Header,
    Header\ContentType,
    Header\ContentTypeValue,
    Header\Parameter,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class CharsetParserTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Parser::class,
            new CharsetParser
        );
    }

    public function testKey()
    {
        $this->assertSame('charset', CharsetParser::key());
    }

    public function testDoesntParseWhenNoContentType()
    {
        $parse = new CharsetParser;
        $request = $this->createMock(Request::class);
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
            ->with('Content-Type')
            ->willReturn(false);
        $expected = new Map('string', Attribute::class);

        $attributes = $parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenContentTypeNotFullyParsed()
    {
        $parse = new CharsetParser;
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->exactly(2))
            ->method('headers')
            ->willReturn(
                $headers = $this->createMock(Headers::class)
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
            ->willReturn($this->createMock(Header::class));
        $expected = new Map('string', Attribute::class);

        $attributes = $parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenNoCharset()
    {
        $parse = new CharsetParser;
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->exactly(2))
            ->method('headers')
            ->willReturn(
                $headers = $this->createMock(Headers::class)
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
                        new Map('string', Parameter::class)
                    )
                )
            );
        $expected = new Map('string', Attribute::class);

        $attributes = $parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $parse = new CharsetParser;
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->exactly(2))
            ->method('headers')
            ->willReturn(
                $headers = $this->createMock(Headers::class)
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
                        Map::of('string', Parameter::class)
                            (
                                'charset',
                                new Parameter\Parameter('charset', 'utf-8')
                            )
                    )
                )
            );
        $expected = new Map('string', Attribute::class);

        $attributes = $parse($request, $response, $expected);

        $this->assertNotSame($expected, $attributes);
        $this->assertCount(1, $attributes);
        $this->assertSame('charset', $attributes->key());
        $this->assertSame('charset', $attributes->current()->name());
        $this->assertSame('utf-8', $attributes->current()->content());
    }
}
