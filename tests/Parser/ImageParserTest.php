<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser;

use Innmind\Crawler\{
    Parser\ImageParser,
    Parser\Http\ContentTypeParser,
    Parser,
    HttpResource\Attribute,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\MediaType\MediaType;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class ImageParserTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Parser::class,
            new ImageParser($this->createMock(Parser::class))
        );
        $this->assertSame('image', ImageParser::key());
    }

    public function testReturnEarlyWhenNoTypeDefined()
    {
        $parse = new ImageParser(
            $inner = $this->createMock(Parser::class)
        );
        $inner
            ->expects($this->never())
            ->method('__invoke');
        $attributes = Map::of('string', Attribute::class);

        $this->assertSame(
            $attributes,
            $parse(
                $this->createMock(Request::class),
                $this->createMock(Response::class),
                $attributes
            )
        );
    }

    public function testReturnEarlyWhenNotOfExpectedType()
    {
        $parse = new ImageParser(
            $inner = $this->createMock(Parser::class)
        );
        $inner
            ->expects($this->never())
            ->method('__invoke');
        $attributes = Map::of('string', Attribute::class)
            (ContentTypeParser::key(), new Attribute\Attribute(
                ContentTypeParser::key(),
                MediaType::of('text/html')
            ));

        $this->assertSame(
            $attributes,
            $parse(
                $this->createMock(Request::class),
                $this->createMock(Response::class),
                $attributes
            )
        );
    }

    /**
     * @dataProvider types
     */
    public function testCallInnerParserWhenOfExpectedType($type)
    {
        $parse = new ImageParser(
            $inner = $this->createMock(Parser::class)
        );
        $attributes = Map::of('string', Attribute::class)
            (ContentTypeParser::key(), new Attribute\Attribute(
                ContentTypeParser::key(),
                MediaType::of($type)
            ));
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request, $response, $attributes)
            ->willReturn($expected = Map::of('string', Attribute::class));

        $this->assertSame(
            $expected,
            $parse(
                $this->createMock(Request::class),
                $this->createMock(Response::class),
                $attributes
            )
        );
    }

    public function types(): array
    {
        return [
            ['image/jpg'],
            ['image/jpeg'],
            ['image/png'],
            ['image/png+whatever'],
            ['image/vnd.webp+whatever'],
        ];
    }
}
