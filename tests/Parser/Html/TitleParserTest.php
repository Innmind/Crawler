<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser\Html\TitleParser,
    Parser,
    HttpResource\Attribute,
    Parser\Http\ContentTypeParser,
};
use Innmind\Filesystem\{
    MediaType\MediaType,
    Stream\StringStream,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
};
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class TitleParserTest extends TestCase
{
    private $parse;

    public function setUp()
    {
        $this->parse = new TitleParser(html());
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
        $this->assertSame('title', TitleParser::key());
    }

    public function testDoesntParseWhenNothingFound()
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

    /**
     * @dataProvider cases
     */
    public function testParseH1($title, $html)
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
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream($html));

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
        $this->assertSame('title', $attributes->get('title')->name());
        $this->assertSame($title, $attributes->get('title')->content());
    }

    public function cases()
    {
        return [
            ['foobar baz', '<html><body><h1> foobar baz </h1></body></html>'],
            ['foobar baz', '<html><head><title> foobar baz </title></head></html>'],
            ['from body', '<html><head><title>from head</title></head><body><h1>from body</h1></body></html>'],
            ['from head', '<html><head><title>from head</title></head><body><h1>from body</h1><h1>another h1</h1></body></html>'],
        ];
    }
}
