<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser;

use Innmind\Crawler\{
    Parser\SequenceParser,
    Parser,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\MapInterface;
use PHPUnit\Framework\TestCase;

class SequenceParserTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Parser::class,
            new SequenceParser
        );
    }

    public function testKey()
    {
        $this->assertSame('sequence', SequenceParser::key());
    }

    public function testParse()
    {
        $parser1 = $this->createMock(Parser::class);
        $parser2 = $this->createMock(Parser::class);
        $parse = new SequenceParser($parser1, $parser2);

        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $attributes = $this->createMock(MapInterface::class);

        $parser1
            ->expects($this->once())
            ->method('__invoke')
            ->with($request, $response, $attributes)
            ->willReturn($attributes2 = $this->createMock(MapInterface::class));
        $parser2
            ->expects($this->once())
            ->method('__invoke')
            ->with($request, $response, $attributes2)
            ->willReturn($attributes3 = $this->createMock(MapInterface::class));

        $final = $parse($request, $response, $attributes);

        $this->assertSame($attributes3, $final);
    }
}
