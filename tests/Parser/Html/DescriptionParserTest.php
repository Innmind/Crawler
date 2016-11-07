<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser\Html\DescriptionParser,
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

class DescriptionParserTest extends \PHPUnit_Framework_TestCase
{
    private $parser;
    private $clock;

    public function setUp()
    {
        $this->parser = new DescriptionParser(
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
        $this->assertSame('description', DescriptionParser::key());
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

    public function testDoesntParseWhenNoMetaIsADescription()
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
    <meta name="DeScRiPtIoN" content=" lorem  ipsum    foo" />
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
        $this->assertTrue($attributes->contains('description'));
        $description = $attributes->get('description');
        $this->assertSame('description', $description->name());
        $this->assertSame('lorem ipsum foo', $description->content());
        $this->assertSame(42, $description->parsingTime());
    }

    public function testParseVeryLongDescription()
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
    <meta name="DeScRiPtIoN" content="lorem ipsum  \t  dolor sit amet, consectetur adipiscing elit. Nullam vestibulum accumsan massa et sollicitudin. Donec luctus felis sem, in volutpat nisi porttitor eget. Nulla lacus velit, aliquam quis ornare at, pellentesque quis ligula. Aenean lacus nulla, tristique vitae viverra ut, ornare vitae neque. Maecenas in convallis risus. Suspendisse placerat nisi non ornare fringilla. Nam leo sapien, pulvinar sed felis a, pellentesque tincidunt sapien. Proin vulputate sagittis orci sit amet finibus. In non risus dapibus, pharetra urna eget, lobortis mauris. Mauris placerat eget ligula quis porta. Nulla facilisi. In porta tempor lobortis." />
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
        $this->assertTrue($attributes->contains('description'));
        $description = $attributes->get('description');
        $this->assertSame('description', $description->name());
        $this->assertSame(
            'lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam vestibulum accumsan massa et sollicitudin. Donec luctus felis sem, in volutpat nisi po...',
            $description->content()
        );
        $this->assertSame(42, $description->parsingTime());
    }
}
