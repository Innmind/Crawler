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
    Headers,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\unwrap;
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
                Headers::of(),
            );
        $expected = Map::of('string', Attribute::class);

        $attributes = $parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenContentLanguageNotFullyParsed()
    {
        $parse = new LanguagesParser;
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $headers = Headers::of(
            new Header\Header('Content-Language'),
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
        $parse = new LanguagesParser;
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $headers = Headers::of(
            ContentLanguage::of('fr', 'en-US'),
        );
        $response
            ->expects($this->exactly(3))
            ->method('headers')
            ->willReturn($headers);
        $expected = Map::of('string', Attribute::class);

        $attributes = $parse($request, $response, $expected);

        $this->assertNotSame($expected, $attributes);
        $this->assertCount(1, $attributes);
        $this->assertTrue($attributes->contains('languages'));
        $this->assertSame('languages', $attributes->get('languages')->name());
        $this->assertInstanceOf(
            Set::class,
            $attributes->get('languages')->content()
        );
        $this->assertSame(
            'string',
            $attributes->get('languages')->content()->type()
        );
        $this->assertSame(
            ['fr', 'en-US'],
            unwrap($attributes->get('languages')->content()),
        );
    }
}
