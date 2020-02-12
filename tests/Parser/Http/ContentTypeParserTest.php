<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser,
    Parser\Http\ContentTypeParser,
    HttpResource\Attribute,
};
use Innmind\MediaType\MediaType;
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Headers,
    Header,
    Header\ContentType,
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
        $parse = new ContentTypeParser;
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(Headers::of());
        $expected = Map::of('string', Attribute::class);

        $attributes = $parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenContentTypeNotFullyParsed()
    {
        $parse = new ContentTypeParser;
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $headers = Headers::of(
            new Header\Header('Content-Type'),
        );
        $response
            ->expects($this->exactly(2))
            ->method('headers')
            ->willReturn($headers);
        $expected = Map::of('string', Attribute::class);

        $attributes = $parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $parse = new ContentTypeParser;
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $headers = Headers::of(
            ContentType::of('text', 'bar'),
        );
        $response
            ->expects($this->exactly(3))
            ->method('headers')
            ->willReturn($headers);
        $expected = Map::of('string', Attribute::class);

        $attributes = $parse($request, $response, $expected);

        $this->assertNotSame($expected, $attributes);
        $this->assertCount(1, $attributes);
        $this->assertTrue($attributes->contains('content_type'));
        $this->assertSame('content_type', $attributes->get('content_type')->name());
        $this->assertInstanceOf(
            MediaType::class,
            $attributes->get('content_type')->content()
        );
        $this->assertSame('text/bar', $attributes->get('content_type')->content()->toString());
    }
}
