<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser\Html\LinksParser,
    Parser\Http\ContentTypeParser,
    Parser,
    HttpResource\Attribute,
    UrlResolver,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\MediaType\MediaType;
use Innmind\Stream\Readable\Stream;
use Innmind\Url\Url;
use Innmind\UrlResolver\UrlResolver as BaseResolver;
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\unwrap;
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class LinksParserTest extends TestCase
{
    private $parse;

    public function setUp(): void
    {
        $this->parse = new LinksParser(
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
        $this->assertSame('links', LinksParser::key());
    }

    public function testDoesntParseWhenInvalidElements()
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
    <link/>
</head>
<body>
    <a></a>
</body>
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
            ->expects($this->exactly(7))
            ->method('url')
            ->willReturn(Url::of('http://example.com'));
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
    <link rel="first" href="/first"/>
    <link rel="next" href="/next"/>
    <link rel="previous" href="/previous"/>
    <link rel="last" href="/last"/>
    <link rel="whatever" href="/whatever"/>
</head>
<body>
    <a href="/first"></a>
    <a href="/anywhere"></a>
    <a href="/anywhere-else"></a>
</body>
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
        $this->assertTrue($attributes->contains('links'));
        $links = $attributes->get('links');
        $this->assertSame('links', $links->name());
        $this->assertInstanceOf(Set::class, $links->content());
        $this->assertSame(
            Url::class,
            (string) $links->content()->type()
        );
        $this->assertCount(6, $links->content());
        $content = unwrap($links->content());
        $this->assertSame(
            'http://example.com/first',
            \current($content)->toString(),
        );
        \next($content);
        $this->assertSame(
            'http://example.com/next',
            \current($content)->toString(),
        );
        \next($content);
        $this->assertSame(
            'http://example.com/previous',
            \current($content)->toString(),
        );
        \next($content);
        $this->assertSame(
            'http://example.com/last',
            \current($content)->toString(),
        );
        \next($content);
        $this->assertSame(
            'http://example.com/anywhere',
            \current($content)->toString(),
        );
        \next($content);
        $this->assertSame(
            'http://example.com/anywhere-else',
            \current($content)->toString(),
        );
    }
}
