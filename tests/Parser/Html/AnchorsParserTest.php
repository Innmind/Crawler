<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser\Html\AnchorsParser,
    Parser\Http\ContentTypeParser,
    Parser,
    HttpResource\Attribute,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Filesystem\{
    MediaType\MediaType,
    Stream\StringStream,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    SetInterface,
};
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class AnchorsParserTest extends TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new AnchorsParser(html());
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
        $this->assertSame('anchors', AnchorsParser::key());
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

    public function testDoesntParseWhenNoBody()
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
<body>
    <a></a>
    <a href="http://github.com"></a>
    <a href="#bar"></a>
    <a href="#baz"></a>
</body>
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
        $this->assertTrue($attributes->contains('anchors'));
        $anchors = $attributes->get('anchors');
        $this->assertSame('anchors', $anchors->name());
        $this->assertInstanceOf(SetInterface::class, $anchors->content());
        $this->assertSame('string', (string) $anchors->content()->type());
        $this->assertSame(
            ['bar', 'baz'],
            $anchors->content()->toPrimitive()
        );
    }
}
