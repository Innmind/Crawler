<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Crawler\{
    Parser\Html\OpenGraph\TypeParser,
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
use Innmind\Immutable\{
    Map,
    MapInterface
};
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class TypeParserTest extends TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new TypeParser(html());
    }

    public function testInterface()
    {
        $this->assertInstanceOf(Parser::class, $this->parser);
    }

    public function testKey()
    {
        $this->assertSame('type', TypeParser::key());
    }

    public function testDoesntParseWhenNoContentType()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expected = new Map('string', Attribute::class);

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
        $expected = (new Map('string', Attribute::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute\Attribute(
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
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream('<html></html>'));
        $expected = (new Map('string', Attribute::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
                )
            );

        $attributes = $this->parser->parse(
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
</head>
</html>
HTML
            ));
        $notExpected = (new Map('string', Attribute::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
                )
            );

        $attributes = $this->parser->parse(
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
        $this->assertTrue($attributes->contains('type'));
        $type = $attributes->get('type');
        $this->assertSame('type', $type->name());
        $this->assertSame('video.movie', $type->content());
    }
}
