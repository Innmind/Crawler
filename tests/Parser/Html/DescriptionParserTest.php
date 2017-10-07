<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser\Html\DescriptionParser,
    Parser\Http\ContentTypeParser,
    Parser,
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
use Innmind\Http\Message\{
    Request,
    Response
};
use Innmind\Filesystem\{
    MediaType\MediaType,
    Stream\StringStream
};
use Innmind\Immutable\{
    Map,
    MapInterface
};
use PHPUnit\Framework\TestCase;

class DescriptionParserTest extends TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new DescriptionParser(
            new Reader(
                new NodeTranslator(
                    NodeTranslators::defaults()->merge(
                        HtmlTranslators::defaults()
                    )
                )
            )
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Parser::class,
            $this->parser
        );
    }

    public function testKey()
    {
        $this->assertSame('description', DescriptionParser::key());
    }

    public function testDoesntParseWhenNoContentType()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expected = new Map('string', Attribute::class);

        $attributes = $this->parser->parse(
            $request,
            $response,
            $expected
        );

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenNotHtml()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expected = (new Map('string', Attribute::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute\Attribute(
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

    public function testDoesntParseWhenNoHead()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expected = (new Map('string', Attribute::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
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
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expected = (new Map('string', Attribute::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
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
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $notExpected = (new Map('string', Attribute::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
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

        $attributes = $this->parser->parse(
            $request,
            $response,
            $notExpected
        );

        $this->assertNotSame($notExpected, $attributes);
        $this->assertInstanceOf(MapInterface::class, $attributes);
        $this->assertSame('string', (string) $attributes->keyType());
        $this->assertSame(
            Attribute::class,
            (string) $attributes->valueType()
        );
        $this->assertCount(2, $attributes);
        $this->assertTrue($attributes->contains('description'));
        $description = $attributes->get('description');
        $this->assertSame('description', $description->name());
        $this->assertSame('lorem ipsum foo', $description->content());
    }

    public function testParseVeryLongDescription()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $notExpected = (new Map('string', Attribute::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
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

        $attributes = $this->parser->parse(
            $request,
            $response,
            $notExpected
        );

        $this->assertNotSame($notExpected, $attributes);
        $this->assertInstanceOf(MapInterface::class, $attributes);
        $this->assertSame('string', (string) $attributes->keyType());
        $this->assertSame(
            Attribute::class,
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
    }
}
