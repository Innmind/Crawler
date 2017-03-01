<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Crawler\{
    Parser\Html\OpenGraph\ImageParser,
    HttpResource\AttributeInterface,
    HttpResource\Attribute,
    ParserInterface,
    Parser\Http\ContentTypeParser
};
use Innmind\Http\{
    Message\RequestInterface,
    Message\ResponseInterface
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
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    Map,
    MapInterface,
    SetInterface
};
use PHPUnit\Framework\TestCase;

class ImageParserTest extends TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new ImageParser(
            new Reader(
                new NodeTranslator(
                    NodeTranslators::defaults()->merge(
                        HtmlTranslators::defaults()
                    )
                )
            )
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(ParserInterface::class, $this->parser);
    }

    public function testKey()
    {
        $this->assertSame('preview', ImageParser::key());
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

    public function testDoesntParseWhenNoHead()
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

    public function testParse()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<HTML
<html>
<head>
    <meta property="og:title" content="The Rock" />
    <meta property="og:type" content="video.movie" />
    <meta property="og:url" content="http://www.imdb.com/title/tt0117500/" />
    <meta property="og:image" content="http://ia.media-imdb.com/images/rock.jpg" />
    <meta property="og:image" content="http://ia.media-imdb.com/images/rock2.jpg" />
</head>
</html>
HTML
            ));
        $notExpected = (new Map('string', AttributeInterface::class))
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
        $this->assertTrue($attributes->contains('preview'));
        $preview = $attributes->get('preview');
        $this->assertSame('preview', $preview->name());
        $this->assertInstanceOf(
            SetInterface::class,
            $preview->content()
        );
        $this->assertSame(
            UrlInterface::class,
            (string) $preview->content()->type()
        );
        $this->assertCount(2, $preview->content());
        $this->assertSame(
            'http://ia.media-imdb.com/images/rock.jpg',
            (string) $preview->content()->current()
        );
        $preview->content()->next();
        $this->assertSame(
            'http://ia.media-imdb.com/images/rock2.jpg',
            (string) $preview->content()->current()
        );
    }
}
