<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser\Html\AlternatesParser,
    HttpResource\AttributeInterface,
    HttpResource\Alternates,
    HttpResource\Attribute,
    ParserInterface,
    Parser\Http\ContentTypeParser,
    UrlResolver
};
use Innmind\UrlResolver\UrlResolver as BaseResolver;
use Innmind\Url\{
    Url,
    UrlInterface
};
use Innmind\Http\{
    Message\Request,
    Message\RequestInterface,
    Message\ResponseInterface,
    Message\Method,
    ProtocolVersion,
    Headers,
    Header\HeaderInterface
};
use Innmind\Filesystem\{
    Stream\StringStream,
    MediaType\MediaType
};
use Innmind\Html\{
    Reader\Reader,
    Translator\NodeTranslators as HtmlTranslators
};
use Innmind\Xml\Translator\{
    NodeTranslator,
    NodeTranslators
};
use Innmind\Immutable\{
    Map,
    Set,
    SetInterface
};

class AlternatesParserTest extends \PHPUnit_Framework_TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new AlternatesParser(
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
        $this->assertInstanceOf(ParserInterface::class, $this->parser);
    }

    public function testKey()
    {
        $this->assertSame('alternates', AlternatesParser::key());
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

    public function testDoesntParseWhenNoLink()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream('<html></html>'));
        $expected = (new Map('string', AttributeInterface::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
                )
            );

        $attributes = $this->parser->parse(
            $this->createMock(RequestInterface::class),
            $response,
            $expected
        );

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenLinkNotACorrectlyParsedOne()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(
                new StringStream('<html><head><link rel="alternate" href="ios-app://294047850/lmfr/" /></head></html>')
            );
        $expected = (new Map('string', AttributeInterface::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
                )
            );

        $attributes = $this->parser->parse(
            $this->createMock(RequestInterface::class),
            $response,
            $expected
        );

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(
                new StringStream(<<<HTML
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
        $attributes = (new Map('string', AttributeInterface::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
                )
            );

        $attributes = $this->parser->parse(
            new Request(
                Url::fromString('http://example.com/foo/'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers(new Map('string', HeaderInterface::class)),
                new StringStream('')
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
            SetInterface::class,
            $content->get('en')->content()
        );
        $this->assertInstanceOf(
            SetInterface::class,
            $content->get('fr')->content()
        );
        $this->assertSame(
            UrlInterface::class,
            (string) $content->get('en')->content()->type()
        );
        $this->assertSame(
            UrlInterface::class,
            (string) $content->get('fr')->content()->type()
        );
        $this->assertSame(
            'http://example.com/en/',
            (string) $content->get('en')->content()->current()
        );
        $this->assertSame(
            'http://example.com/fr/',
            (string) $content->get('fr')->content()->current()
        );
        $content->get('fr')->content()->next();
        $this->assertSame(
            'http://example.com/fr/foo/',
            (string) $content->get('fr')->content()->current()
        );
    }
}
