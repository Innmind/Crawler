<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser,
    Parser\Http\ContentTypeParser,
    HttpResource\Attribute,
};
use Innmind\Filesystem\MediaType;
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Headers,
    Header,
    Header\ContentType,
    Header\ContentTypeValue,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class ContentTypeParserTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Parser::class,
            new ContentTypeParser
        );
    }

    public function testKey()
    {
        $this->assertSame('content_type', ContentTypeParser::key());
    }

    public function testDoesntParseWhenNoContentType()
    {
        $parser = new ContentTypeParser;
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

        $attributes = $parser->parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenContentTypeNotFullyParsed()
    {
        $parser = new ContentTypeParser;
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $headers = $this->createMock(Headers::class);
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
            ->willReturn($this->createMock(Header::class));
        $expected = new Map('string', Attribute::class);

        $attributes = $parser->parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $parser = new ContentTypeParser;
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $headers = $this->createMock(Headers::class);
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
                        'bar'
                    )
                )
            );
        $expected = new Map('string', Attribute::class);

        $attributes = $parser->parse($request, $response, $expected);

        $this->assertNotSame($expected, $attributes);
        $this->assertCount(1, $attributes);
        $this->assertSame('content_type', $attributes->key());
        $this->assertSame('content_type', $attributes->current()->name());
        $this->assertInstanceOf(
            MediaType::class,
            $attributes->current()->content()
        );
        $this->assertSame('text/bar', (string) $attributes->current()->content());
    }
}
