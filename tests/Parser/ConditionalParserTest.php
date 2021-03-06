<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser;

use Innmind\Crawler\{
    Parser\ConditionalParser,
    Parser,
    HttpResource\Attribute,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class ConditionalParserTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Parser::class,
            new ConditionalParser
        );
    }

    public function testKey()
    {
        $this->assertSame('conditional', ConditionalParser::key());
    }

    public function testParse()
    {
        $parser1 = $this->createMock(Parser::class);
        $parser2 = $this->createMock(Parser::class);
        $parser3 = $this->createMock(Parser::class);
        $parse = new ConditionalParser($parser1, $parser2, $parser3);

        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $attributes = Map::of('string', Attribute::class);

        $parser1
            ->expects($this->once())
            ->method('__invoke')
            ->with($request, $response, $attributes)
            ->willReturn($attributes);
        $parser2
            ->expects($this->once())
            ->method('__invoke')
            ->with($request, $response, $attributes)
            ->willReturn($attributes2 = Map::of('string', Attribute::class));
        $parser2
            ->expects($this->once())
            ->method('__invoke')
            ->with($request, $response, $attributes2)
            ->willReturn($attributes3 = Map::of('string', Attribute::class));

        $final = $parse($request, $response, $attributes);

        $this->assertSame($attributes2, $final);
    }
}
