<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Crawler\{
    Parser\Html\OpenGraph\ImageParser,
    HttpResource\Attribute,
    Parser,
    Parser\Http\ContentTypeParser,
};
use Innmind\Http\{
    Message\Request,
    Message\Response,
};
use Innmind\Filesystem\{
    Stream\StringStream,
    MediaType\MediaType,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    Map,
    MapInterface,
    SetInterface
};
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class ImageParserTest extends TestCase
{
    private $parse;

    public function setUp()
    {
        $this->parse = new ImageParser(html());
    }

    public function testInterface()
    {
        $this->assertInstanceOf(Parser::class, $this->parse);
    }

    public function testKey()
    {
        $this->assertSame('preview', ImageParser::key());
    }

    public function testDoesntParseWhenNoHead()
    {
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream('<html></html>'));
        $expected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
                )
            );

        $attributes = ($this->parse)(
            $this->createMock(Request::class),
            $response,
            $expected
        );

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenEmptyMeta()
    {
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<HTML
<html>
<head>
    <meta property="og:title" content="The Rock" />
    <meta property="og:type" content="video.movie" />
    <meta property="og:url" content="http://www.imdb.com/title/tt0117500/" />
    <meta property="og:image" content="" />
</head>
</html>
HTML
        ));
        $expected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
                )
            );

        $attributes = ($this->parse)(
            $this->createMock(Request::class),
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
        $notExpected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
                )
            );

        $attributes = ($this->parse)(
            $this->createMock(Request::class),
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
