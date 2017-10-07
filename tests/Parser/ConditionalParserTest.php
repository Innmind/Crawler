<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser;

use Innmind\Crawler\{
    Parser\ConditionalParser,
    ParserInterface
};
use Innmind\Http\Message\{
    Request,
    Response
};
use Innmind\Immutable\MapInterface;
use PHPUnit\Framework\TestCase;

class ConditionalParserTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            ParserInterface::class,
            new ConditionalParser
        );
    }

    public function testKey()
    {
        $this->assertSame('conditional', ConditionalParser::key());
    }

    public function testParse()
    {
        $parser1 = $this->createMock(ParserInterface::class);
        $parser2 = $this->createMock(ParserInterface::class);
        $parser3 = $this->createMock(ParserInterface::class);
        $parser = new ConditionalParser($parser1, $parser2, $parser3);

        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $attributes = $this->createMock(MapInterface::class);

        $parser1
            ->expects($this->once())
            ->method('parse')
            ->with($request, $response, $attributes)
            ->willReturn($attributes);
        $parser2
            ->expects($this->once())
            ->method('parse')
            ->with($request, $response, $attributes)
            ->willReturn($attributes2 = $this->createMock(MapInterface::class));
        $parser2
            ->expects($this->once())
            ->method('parse')
            ->with($request, $response, $attributes2)
            ->willReturn($attributes3 = $this->createMock(MapInterface::class));

        $final = $parser->parse($request, $response, $attributes);

        $this->assertSame($attributes2, $final);
    }
}
