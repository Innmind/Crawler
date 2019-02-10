<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser,
    Parser\Http\LanguagesParser,
    HttpResource\Attribute,
};
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Header,
    Header\ContentLanguage,
    Header\ContentLanguageValue,
    Headers,
};
use Innmind\Immutable\{
    Map,
    SetInterface,
};
use PHPUnit\Framework\TestCase;

class LanguagesParserTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Parser::class,
            new LanguagesParser
        );
    }

    public function testKey()
    {
        $this->assertSame('languages', LanguagesParser::key());
    }

    public function testDoesntParseWhenNoContentLanguage()
    {
        $parse = new LanguagesParser;
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
            ->with('Content-Language')
            ->willReturn(false);
        $expected = new Map('string', Attribute::class);

        $attributes = $parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenContentLanguageNotFullyParsed()
    {
        $parse = new LanguagesParser;
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
            ->with('Content-Language')
            ->willReturn(true);
        $headers
            ->expects($this->once())
            ->method('get')
            ->with('Content-Language')
            ->willReturn($this->createMock(Header::class));
        $expected = new Map('string', Attribute::class);

        $attributes = $parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $parse = new LanguagesParser;
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
            ->with('Content-Language')
            ->willReturn(true);
        $headers
            ->expects($this->exactly(2))
            ->method('get')
            ->with('Content-Language')
            ->willReturn(
                new ContentLanguage(
                    new ContentLanguageValue('fr'),
                    new ContentLanguageValue('en-US')
                )
            );
        $expected = new Map('string', Attribute::class);

        $attributes = $parse($request, $response, $expected);

        $this->assertNotSame($expected, $attributes);
        $this->assertCount(1, $attributes);
        $this->assertSame('languages', $attributes->key());
        $this->assertSame('languages', $attributes->current()->name());
        $this->assertInstanceOf(
            SetInterface::class,
            $attributes->current()->content()
        );
        $this->assertSame(
            'string',
            (string) $attributes->current()->content()->type()
        );
        $this->assertSame(
            ['fr', 'en-US'],
            $attributes->current()->content()->toPrimitive()
        );
    }
}
