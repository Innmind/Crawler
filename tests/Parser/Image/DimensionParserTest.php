<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Image;

use Innmind\Crawler\{
    Parser\Image\DimensionParser,
    HttpResource\Attribute,
    HttpResource\Attributes,
    Parser,
    Parser\Http\ContentTypeParser,
};
use Innmind\Http\{
    Message\Request,
    Message\Response,
};
use Innmind\Filesystem\MediaType\MediaType;
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class DimensionParserTest extends TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new DimensionParser;
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Parser::class,
            $this->parser
        );
    }

    public function testKey()
    {
        $this->assertSame('dimension', DimensionParser::key());
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

    public function testDoesntParseWhenNotImage()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expected = Map::of('string', Attribute::class)
            (
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

    public function testParse()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $notExpected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('image/jpg')
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new Stream(fopen('fixtures/dont_panic.jpg', 'r')));

        $attributes = $this->parser->parse(
            $request,
            $response,
            $notExpected
        );

        $this->assertNotSame($notExpected, $attributes);
        $this->assertTrue($attributes->contains('dimension'));
        $dimension = $attributes->get('dimension');
        $this->assertInstanceOf(Attributes::class, $dimension);
        $this->assertTrue($dimension->content()->contains('width'));
        $this->assertTrue($dimension->content()->contains('height'));
        $width = $dimension->content()->get('width');
        $this->assertSame('width', $width->name());
        $this->assertSame(604, $width->content());
        $height = $dimension->content()->get('height');
        $this->assertSame('height', $height->name());
        $this->assertSame(800, $height->content());
    }
}
