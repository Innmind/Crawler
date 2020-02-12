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
            ->willReturn(Headers::of());
        $expected = Map::of('string', Attribute::class);

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
                Headers::of(
                    new Header\Header('Content-Type'),
                )
            );
        $expected = Map::of('string', Attribute::class);

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
                Headers::of(
                    ContentType::of('foo', 'bar')
                )
            );
        $expected = Map::of('string', Attribute::class);

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
                Headers::of(
                    ContentType::of(
                        'foo',
                        'bar',
                        new Parameter\Parameter('charset', 'utf-8')
                    )
                )
            );
        $expected = Map::of('string', Attribute::class);

        $attributes = $parse($request, $response, $expected);

        $this->assertNotSame($expected, $attributes);
        $this->assertCount(1, $attributes);
        $this->assertTrue($attributes->contains('charset'));
        $this->assertSame('charset', $attributes->get('charset')->name());
        $this->assertSame('utf-8', $attributes->get('charset')->content());
    }
}
