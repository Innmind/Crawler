<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser\Html\RssParser,
    Parser\Http\ContentTypeParser,
    Parser,
    HttpResource\Attribute,
    UrlResolver,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Filesystem\{
    MediaType\MediaType,
    Stream\StringStream,
};
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\UrlResolver\UrlResolver as BaseResolver;
use Innmind\Immutable\{
    MapInterface,
    Map,
};
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class RssParserTest extends TestCase
{
    private $parse;

    public function setUp()
    {
        $this->parse = new RssParser(
            html(),
            new UrlResolver(new BaseResolver)
        );
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
        $this->assertSame('rss', RssParser::key());
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
                    MediaType::fromString('text/html')
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream('<html></html>'));

        $attributes = ($this->parse)(
            $request,
            $response,
            $expected
        );

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenNoLinks()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<HTML
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

    public function testParse()
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('url')
            ->willReturn(Url::fromString('http://example.com'));
        $response = $this->createMock(Response::class);
        $notExpected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <link rel="alternate" type="application/rss+xml" href="rss" />
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
        $this->assertInstanceOf(MapInterface::class, $attributes);
        $this->assertSame('string', (string) $attributes->keyType());
        $this->assertSame(
            Attribute::class,
            (string) $attributes->valueType()
        );
        $this->assertCount(2, $attributes);
        $this->assertTrue($attributes->contains('rss'));
        $rss = $attributes->get('rss');
        $this->assertSame('rss', $rss->name());
        $this->assertInstanceOf(UrlInterface::class, $rss->content());
        $this->assertSame('http://example.com/rss', (string) $rss->content());
    }
}
