<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Image;

use Innmind\Crawler\{
    Parser\Image\DimensionParser,
    HttpResource\AttributeInterface,
    HttpResource\Attribute,
    HttpResource\Attributes,
    ParserInterface,
    Parser\Http\ContentTypeParser
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PointInTimeInterface,
    ElapsedPeriod
};
use Innmind\Http\{
    Message\RequestInterface,
    Message\ResponseInterface
};
use Innmind\Filesystem\{
    Stream\Stream,
    MediaType\MediaType
};
use Innmind\Immutable\Map;

class DimensionParserTest extends \PHPUnit_Framework_TestCase
{
    private $parser;
    private $clock;

    public function setUp()
    {
        $this->parser = new DimensionParser(
            $this->clock = $this->createMock(TimeContinuumInterface::class)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            ParserInterface::class,
            $this->parser
        );
    }

    public function testKey()
    {
        $this->assertSame('dimension', DimensionParser::key());
    }

    public function testDoesntParseWhenNoContentType()
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $expected = new Map('string', AttributeInterface::class);

        $attributes = $this->parser->parse(
            $request,
            $response,
            $expected
        );

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenNotImage()
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $expected = (new Map('string', AttributeInterface::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/csv'),
                    0
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
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $notExpected = (new Map('string', AttributeInterface::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('image/jpg'),
                    0
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::fromPath('fixtures/dont_panic.jpg'));
        $this
            ->clock
            ->expects($this->exactly(2))
            ->method('now')
            ->will(
                $this->onConsecutiveCalls(
                    $start = $this->createMock(PointInTimeInterface::class),
                    $end = $this->createMock(PointInTimeInterface::class)
                )
            );
        $end
            ->expects($this->once())
            ->method('elapsedSince')
            ->with($start)
            ->willReturn(new ElapsedPeriod(42));

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
        $this->assertSame(42, $width->parsingTime());
        $height = $dimension->content()->get('height');
        $this->assertSame('height', $height->name());
        $this->assertSame(800, $height->content());
        $this->assertSame(42, $height->parsingTime());
    }
}
