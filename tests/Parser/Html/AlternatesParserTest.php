<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser\Html\AlternatesParser,
    HttpResource\Attribute,
    HttpResource\Alternates,
    Parser,
    Parser\Http\ContentTypeParser,
    UrlResolver,
};
use Innmind\UrlResolver\UrlResolver as BaseResolver;
use Innmind\Url\Url;
use Innmind\Http\{
    Message\Request\Request,
    Message\Request as RequestInterface,
    Message\Response,
    Message\Method,
    ProtocolVersion,
};
use Innmind\Stream\Readable\Stream;
use Innmind\MediaType\MediaType;
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\{
    first,
    unwrap,
};
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class AlternatesParserTest extends TestCase
{
    private $parse;

    public function setUp(): void
    {
        $this->parse = new AlternatesParser(
            html(),
            new UrlResolver(new BaseResolver)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(Parser::class, $this->parse);
    }

    public function testKey()
    {
        $this->assertSame('alternates', AlternatesParser::key());
    }

    public function testDoesntParseWhenNoLink()
    {
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent('<html></html>'));
        $expected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::of('text/html')
                )
            );

        $attributes = ($this->parse)(
            $this->createMock(RequestInterface::class),
            $response,
            $expected
        );

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenLinkNotACorrectlyParsedOne()
    {
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(
                Stream::ofContent('<html><head><link rel="alternate" href="ios-app://294047850/lmfr/" /></head></html>')
            );
        $expected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::of('text/html')
                )
            );

        $attributes = ($this->parse)(
            $this->createMock(RequestInterface::class),
            $response,
            $expected
        );

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(
                Stream::ofContent(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <link rel="alternate" hreflang="fr" href="/fr/" />
    <link rel="alternate" hreflang="fr" href="/fr/foo/" />
    <link rel="alternate" hreflang="en" href="/en/" />
</head>
<body>
</body>
</html>
HTML
                )
            );
        $attributes = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::of('text/html')
                )
            );

        $attributes = ($this->parse)(
            new Request(
                Url::of('http://example.com/foo/'),
                new Method('GET'),
                new ProtocolVersion(1, 1)
            ),
            $response,
            $attributes
        );

        $this->assertTrue($attributes->contains('alternates'));
        $alternates = $attributes->get('alternates');
        $this->assertInstanceOf(Alternates::class, $alternates);
        $content = $alternates->content();
        $this->assertCount(2, $content);
        $this->assertTrue($content->contains('en'));
        $this->assertTrue($content->contains('fr'));
        $this->assertInstanceOf(
            Set::class,
            $content->get('en')->content()
        );
        $this->assertInstanceOf(
            Set::class,
            $content->get('fr')->content()
        );
        $this->assertSame(
            Url::class,
            (string) $content->get('en')->content()->type()
        );
        $this->assertSame(
            Url::class,
            (string) $content->get('fr')->content()->type()
        );
        $this->assertSame(
            'http://example.com/en/',
            first($content->get('en')->content())->toString()
        );
        $content = unwrap($content->get('fr')->content());
        $this->assertSame(
            'http://example.com/fr/',
            \current($content)->toString(),
        );
        \next($content);
        $this->assertSame(
            'http://example.com/fr/foo/',
            \current($content)->toString(),
        );
    }
}
