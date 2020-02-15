<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser\Html\BaseParser,
    Parser\Http\ContentTypeParser,
    Parser,
    HttpResource\Attribute,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\MediaType\MediaType;
use Innmind\Stream\Readable\Stream;
use Innmind\Url\Url;
use Innmind\Immutable\Map;
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class BaseParserTest extends TestCase
{
    private $parse;

    public function setUp(): void
    {
        $this->parse = new BaseParser(html());
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Parser::class,
            $this->parse
        );
    }

    public function testKey()
    {
        $this->assertSame('base', BaseParser::key());
    }

    public function testDoesntParseWhenNoHead()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::of('text/html')
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent('<html></html>'));

        $attributes = ($this->parse)(
            $request,
            $response,
            $expected
        );

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenNoBase()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::of('text/html')
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<HTML
<!DOCTYPE html>
<html>
<head>
</head>
</html>
HTML
            ));

        $attributes = ($this->parse)(
            $request,
            $response,
            $expected
        );

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenInvalidBaseElement()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::of('text/html')
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <base/>
</head>
</html>
HTML
            ));

        $attributes = ($this->parse)(
            $request,
            $response,
            $expected
        );

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $notExpected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::of('text/html')
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <base href="/foo"/>
</head>
</html>
HTML
            ));

        $attributes = ($this->parse)(
            $request,
            $response,
            $notExpected
        );

        $this->assertNotSame($notExpected, $attributes);
        $this->assertInstanceOf(Map::class, $attributes);
        $this->assertSame('string', (string) $attributes->keyType());
        $this->assertSame(
            Attribute::class,
            (string) $attributes->valueType()
        );
        $this->assertCount(2, $attributes);
        $this->assertTrue($attributes->contains('base'));
        $base = $attributes->get('base');
        $this->assertSame('base', $base->name());
        $this->assertInstanceOf(Url::class, $base->content());
        $this->assertSame('/foo', $base->content()->toString());
    }
}
