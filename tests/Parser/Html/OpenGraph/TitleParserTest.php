<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Crawler\{
    Parser\Html\OpenGraph\TitleParser,
    HttpResource\Attribute,
    Parser,
    Parser\Http\ContentTypeParser,
};
use Innmind\Http\{
    Message\Request,
    Message\Response,
};
use Innmind\Stream\Readable\Stream;
use Innmind\MediaType\MediaType;
use Innmind\Immutable\Map;
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class TitleParserTest extends TestCase
{
    private $parse;

    public function setUp(): void
    {
        $this->parse = new TitleParser(html());
    }

    public function testInterface()
    {
        $this->assertInstanceOf(Parser::class, $this->parse);
    }

    public function testKey()
    {
        $this->assertSame('title', TitleParser::key());
    }

    public function testDoesntParseWhenNoHead()
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
            ->willReturn(Stream::ofContent(<<<HTML
<html>
<head>
    <meta property="og:title" content="The Rock" />
    <meta property="og:type" content="video.movie" />
    <meta property="og:url" content="http://www.imdb.com/title/tt0117500/" />
    <meta property="og:image" content="http://ia.media-imdb.com/images/rock.jpg" />
</head>
</html>
HTML
            ));
        $notExpected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::of('text/html')
                )
            );

        $attributes = ($this->parse)(
            $this->createMock(Request::class),
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
        $this->assertTrue($attributes->contains('title'));
        $title = $attributes->get('title');
        $this->assertSame('title', $title->name());
        $this->assertSame('The Rock', $title->content());
    }
}
