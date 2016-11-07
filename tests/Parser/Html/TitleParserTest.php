<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser\Html\TitleParser,
    ParserInterface,
    HttpResource\AttributeInterface,
    HttpResource\Attribute,
    Parser\Http\ContentTypeParser
};
use Innmind\Xml\Translator\{
    NodeTranslator,
    NodeTranslators
};
use Innmind\Html\{
    Reader\Reader,
    Translator\NodeTranslators as HtmlTranslators
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PointInTimeInterface,
    ElapsedPeriod
};
use Innmind\Filesystem\{
    MediaType\MediaType,
    Stream\StringStream
};
use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Innmind\Immutable\{
    Map,
    MapInterface
};

class TitleParserTest extends \PHPUnit_Framework_TestCase
{
    private $parser;
    private $clock;

    public function setUp()
    {
        $this->parser = new TitleParser(
            new Reader(
                new NodeTranslator(
                    NodeTranslators::defaults()->merge(
                        HtmlTranslators::defaults()
                    )
                )
            ),
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
        $this->assertSame('title', TitleParser::key());
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

    public function testDoesntParseWhenNotHtml()
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

    public function testDoesntParseWhenNothingFound()
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $expected = (new Map('string', AttributeInterface::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html'),
                    0
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream('<html></html>'));

        $attributes = $this->parser->parse(
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
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $notExpected = (new Map('string', AttributeInterface::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html'),
                    0
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream($html));
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
        $this->assertInstanceOf(MapInterface::class, $attributes);
        $this->assertSame('string', (string) $attributes->keyType());
        $this->assertSame(
            AttributeInterface::class,
            (string) $attributes->valueType()
        );
        $this->assertSame('title', $attributes->get('title')->name());
        $this->assertSame($title, $attributes->get('title')->content());
        $this->assertSame(42, $attributes->get('title')->parsingTime());
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
