<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser\Html\IosParser,
    Parser\Http\ContentTypeParser,
    ParserInterface,
    HttpResource\AttributeInterface,
    HttpResource\Attribute
};
use Innmind\Html\{
    Reader\Reader,
    Translator\NodeTranslators as HtmlTranslators
};
use Innmind\Xml\Translator\{
    NodeTranslator,
    NodeTranslators
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PointInTimeInterface,
    ElapsedPeriod
};
use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Innmind\Filesystem\{
    MediaType\MediaType,
    Stream\StringStream
};
use Innmind\Immutable\{
    Map,
    MapInterface
};

class IosParserTest extends \PHPUnit_Framework_TestCase
{
    private $parser;
    private $clock;

    public function setUp()
    {
        $this->parser = new IosParser(
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
        $this->assertSame('ios', IosParser::key());
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

    public function testDoesntParseWhenNoHead()
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

    public function testDoesntParseWhenNoMetaIsExpectedOne()
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
            ->willReturn(new StringStream(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta property="og:type" content="website" />
    <meta name="og:locale" content="fr_FR" />
</head>
</html>
HTML
            ));

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
                    MediaType::fromString('text/html'),
                    0
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta property="og:type" content="website" />
    <meta name="og:locale" content="fr_FR" />
    <meta name="apple-itunes-app" content="app-id=42, affiliate-data=foo, app-argument=innmind://">
</head>
</html>
HTML
            ));
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
        $this->assertCount(2, $attributes);
        $this->assertTrue($attributes->contains('ios'));
        $ios = $attributes->get('ios');
        $this->assertSame('ios', $ios->name());
        $this->assertSame('innmind://', $ios->content());
        $this->assertSame(42, $ios->parsingTime());
    }
}