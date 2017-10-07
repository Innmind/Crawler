<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser\Html\RssParser,
    Parser\Http\ContentTypeParser,
    ParserInterface,
    HttpResource\AttributeInterface,
    HttpResource\Attribute,
    UrlResolver
};
use Innmind\Html\{
    Reader\Reader,
    Translator\NodeTranslators as HtmlTranslators
};
use Innmind\Xml\Translator\{
    NodeTranslator,
    NodeTranslators
};
use Innmind\Http\Message\{
    Request,
    Response
};
use Innmind\Filesystem\{
    MediaType\MediaType,
    Stream\StringStream
};
use Innmind\Url\{
    UrlInterface,
    Url
};
use Innmind\UrlResolver\UrlResolver as BaseResolver;
use Innmind\Immutable\{
    Map,
    MapInterface
};
use PHPUnit\Framework\TestCase;

class RssParserTest extends TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new RssParser(
            new Reader(
                new NodeTranslator(
                    NodeTranslators::defaults()->merge(
                        HtmlTranslators::defaults()
                    )
                )
            ),
            new UrlResolver(new BaseResolver)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            ParserInterface::class,
            $this->parser
        );
    }

    public function testKey()
    {
        $this->assertSame('rss', RssParser::key());
    }

    public function testDoesntParseWhenNoContentType()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expected = new Map('string', AttributeInterface::class);

        $attributes = $this->parser->parse(
            $request,
            $response,
            $expected
        );

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenNotHtml()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expected = (new Map('string', AttributeInterface::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/csv')
                )
            );

        $attributes = $this->parser->parse(
            $request,
            $response,
            $expected
        );

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenNoHead()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expected = (new Map('string', AttributeInterface::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream('<html></html>'));

        $attributes = $this->parser->parse(
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
        $expected = (new Map('string', AttributeInterface::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute(
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

        $attributes = $this->parser->parse(
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
        $notExpected = (new Map('string', AttributeInterface::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute(
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

        $attributes = $this->parser->parse(
            $request,
            $response,
            $notExpected
        );

        $this->assertNotSame($notExpected, $attributes);
        $this->assertInstanceOf(MapInterface::class, $attributes);
        $this->assertSame('string', (string) $attributes->keyType());
        $this->assertSame(
            AttributeInterface::class,
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
