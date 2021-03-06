<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Image;

use Innmind\Crawler\{
    Parser\Image\WeightParser,
    HttpResource\Attribute,
    Parser,
    Parser\Http\ContentTypeParser,
};
use Innmind\Http\{
    Message\Request,
    Message\Response,
};
use Innmind\MediaType\MediaType;
use Innmind\Stream\{
    Readable,
    Stream\Size,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class WeightParserTest extends TestCase
{
    private $parse;

    public function setUp(): void
    {
        $this->parse = new WeightParser;
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
        $this->assertSame('weight', WeightParser::key());
    }

    public function testDoesntParseWhenSizeNotKnown()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::of('image/png')
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(
                $stream = $this->createMock(Readable::class)
            );
        $stream
            ->expects($this->once())
            ->method('knowsSize')
            ->willReturn(false);

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
                    MediaType::of('image/jpg')
                )
            );
        $response
            ->expects($this->exactly(2))
            ->method('body')
            ->willReturn(
                $stream = $this->createMock(Readable::class)
            );
        $stream
            ->expects($this->once())
            ->method('knowsSize')
            ->willReturn(true);
        $stream
            ->expects($this->once())
            ->method('size')
            ->willReturn(new Size(66));

        $attributes = ($this->parse)(
            $request,
            $response,
            $notExpected
        );

        $this->assertNotSame($notExpected, $attributes);
        $this->assertTrue($attributes->contains('weight'));
        $weight = $attributes->get('weight');
        $this->assertSame('weight', $weight->name());
        $this->assertInstanceOf(Size::class, $weight->content());
        $this->assertSame(66, $weight->content()->toInt());
    }
}
