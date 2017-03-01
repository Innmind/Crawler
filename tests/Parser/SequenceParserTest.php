<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser;

use Innmind\Crawler\{
    Parser\SequenceParser,
    ParserInterface
};
use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Innmind\Immutable\MapInterface;
use PHPUnit\Framework\TestCase;

class SequenceParserTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            ParserInterface::class,
            new SequenceParser
        );
    }

    public function testKey()
    {
        $this->assertSame('sequence', SequenceParser::key());
    }

    public function testParse()
    {
        $parser1 = $this->createMock(ParserInterface::class);
        $parser2 = $this->createMock(ParserInterface::class);
        $parser = new SequenceParser($parser1, $parser2);

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $attributes = $this->createMock(MapInterface::class);

        $parser1
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

        $this->assertSame($attributes3, $final);
    }
}
