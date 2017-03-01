<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser\Html\LinksParser,
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
    RequestInterface,
    ResponseInterface
};
use Innmind\Filesystem\{
    MediaType\MediaType,
    Stream\StringStream
};
use Innmind\Url\{
    Url,
    UrlInterface
};
use Innmind\UrlResolver\UrlResolver as BaseResolver;
use Innmind\Immutable\{
    Map,
    MapInterface,
    SetInterface
};

class LinksParserTest extends \PHPUnit_Framework_TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new LinksParser(
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
        $this->assertSame('links', LinksParser::key());
    }

    public function testDoesntParseWhenNoContentType()
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
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
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
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

    public function testDoesntParseWhenInvalidElements()
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
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
    <link/>
</head>
<body>
    <a></a>
</body>
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
        $request = $this->createMock(RequestInterface::class);
        $request
            ->expects($this->exactly(7))
            ->method('url')
            ->willReturn(Url::fromString('http://example.com'));
        $response = $this->createMock(ResponseInterface::class);
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
        $this->assertTrue($attributes->contains('links'));
        $links = $attributes->get('links');
        $this->assertSame('links', $links->name());
        $this->assertInstanceOf(SetInterface::class, $links->content());
        $this->assertSame(
            UrlInterface::class,
            (string) $links->content()->type()
        );
        $this->assertCount(6, $links->content());
        $this->assertSame(
            'http://example.com/first',
            (string) $links->content()->current()
        );
        $links->content()->next();
        $this->assertSame(
            'http://example.com/next',
            (string) $links->content()->current()
        );
        $links->content()->next();
        $this->assertSame(
            'http://example.com/previous',
            (string) $links->content()->current()
        );
        $links->content()->next();
        $this->assertSame(
            'http://example.com/last',
            (string) $links->content()->current()
        );
        $links->content()->next();
        $this->assertSame(
            'http://example.com/anywhere',
            (string) $links->content()->current()
        );
        $links->content()->next();
        $this->assertSame(
            'http://example.com/anywhere-else',
             (string) $links->content()->current()
            );
    }
}
