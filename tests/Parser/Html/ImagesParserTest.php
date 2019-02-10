<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser\Html\ImagesParser,
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
    SetInterface,
};
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class ImagesParserTest extends TestCase
{
    private $parse;

    public function setUp(): void
    {
        $this->parse = new ImagesParser(
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
        $this->assertSame('images', ImagesParser::key());
    }

    public function testDoesntParseWhenNoBody()
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

    public function testDoesntParseWhenNoImages()
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
<body>
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
        $response = $this->createMock(Response::class);
        $notExpected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
                )
            );
        $request
            ->expects($this->exactly(6))
            ->method('url')
            ->willReturn(Url::fromString('http://github.com'));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<HTML
<!DOCTYPE html>
<html>
<body>
    <figure>foo</figure>
    <figure><figcaption>bar</figcaption></figure>
    <figure>
        <img src="foo.png" alt="baz" />
        <figcaption>bar</figcaption>
    </figure>
    <figure>
        <img src="bar.png" alt="baz" />
    </figure>
    <img src="foo.png" alt="foo" />
    <img src="baz.png" alt="foobar" />
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
        $this->assertInstanceOf(MapInterface::class, $attributes);
        $this->assertSame('string', (string) $attributes->keyType());
        $this->assertSame(
            Attribute::class,
            (string) $attributes->valueType()
        );
        $this->assertCount(2, $attributes);
        $this->assertTrue($attributes->contains('images'));
        $images = $attributes->get('images');
        $this->assertSame('images', $images->name());
        $this->assertInstanceOf(MapInterface::class, $images->content());
        $this->assertSame(
            UrlInterface::class,
            (string) $images->content()->keyType()
        );
        $this->assertSame('string', (string) $images->content()->valueType());
        $map = $images->content();
        $this->assertCount(3, $map);
        $this->assertSame('http://github.com/foo.png', (string) $map->key());
        $this->assertSame('bar', $map->current());
        $map->next();
        $this->assertSame('http://github.com/bar.png', (string) $map->key());
        $this->assertSame('baz', $map->current());
        $map->next();
        $this->assertSame('http://github.com/baz.png', (string) $map->key());
        $this->assertSame('foobar', $map->current());
    }
}
