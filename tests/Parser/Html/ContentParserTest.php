<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser\Html\ContentParser,
    Parser\Http\ContentTypeParser,
    Parser,
    HttpResource\Attribute,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\MediaType\MediaType;
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Map;
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class ContentParserTest extends TestCase
{
    private $parse;

    public function setUp(): void
    {
        $this->parse = new ContentParser(html());
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Parser::class,
            $this->parse
        );
    }

    public function testKey()
    {
        $this->assertSame('content', ContentParser::key());
    }

    public function testDoesntParseWhenNoBody()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $expected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::of('text/html')
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent('<html></html>'));

        $attributes = ($this->parse)(
            $request,
            $response,
            $expected
        );

        $this->assertSame($expected, $attributes);
    }

    /**
     * @dataProvider fixtures
     */
    public function testParse(string $fixture)
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $notExpected = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::of('text/html')
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new Stream(\fopen($fixture, 'r')));

        $attributes = ($this->parse)(
            $request,
            $response,
            $notExpected
        );

        $this->assertNotSame($notExpected, $attributes);
        $this->assertInstanceOf(Map::class, $attributes);
        $this->assertSame('string', (string) $attributes->keyType());
        $this->assertSame(
            Attribute::class,
            (string) $attributes->valueType()
        );
        $this->assertCount(2, $attributes);
        $this->assertTrue($attributes->contains('content'));
        $content = $attributes->get('content');
        $this->assertSame('content', $content->name());
        $this->assertTrue(\is_string($content->content()));
        $this->assertFalse(empty($content->content()));
    }

    public function fixtures(): array
    {
        return [
            ['fixtures/h2g2.html'],
            ['fixtures/lemonde.html'],
            ['fixtures/reddit.html'],
            ['fixtures/medium.html'],
        ];
    }
}
