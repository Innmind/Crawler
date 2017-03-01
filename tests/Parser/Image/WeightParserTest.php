<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Image;

use Innmind\Crawler\{
    Parser\Image\WeightParser,
    HttpResource\AttributeInterface,
    HttpResource\Attribute,
    ParserInterface,
    Parser\Http\ContentTypeParser
};
use Innmind\Http\{
    Message\RequestInterface,
    Message\ResponseInterface
};
use Innmind\Filesystem\{
    StreamInterface,
    MediaType\MediaType
};
use Innmind\Immutable\Map;

class WeightParserTest extends \PHPUnit_Framework_TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new WeightParser;
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
        $this->assertSame('weight', WeightParser::key());
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

    public function testDoesntParseWhenSizeNotKnown()
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $expected = (new Map('string', AttributeInterface::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('image/png')
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(
                $stream = $this->createMock(StreamInterface::class)
            );
        $stream
            ->expects($this->once())
            ->method('knowsSize')
            ->willReturn(false);

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
                    MediaType::fromString('image/jpg')
                )
            );
        $response
            ->expects($this->exactly(2))
            ->method('body')
            ->willReturn(
                $stream = $this->createMock(StreamInterface::class)
            );
        $stream
            ->expects($this->once())
            ->method('knowsSize')
            ->willReturn(true);
        $stream
            ->expects($this->once())
            ->method('size')
            ->willReturn(66);

        $attributes = $this->parser->parse(
            $request,
            $response,
            $notExpected
        );

        $this->assertNotSame($notExpected, $attributes);
        $this->assertTrue($attributes->contains('weight'));
        $weight = $attributes->get('weight');
        $this->assertSame('weight', $weight->name());
        $this->assertSame(66, $weight->content());
    }
}
