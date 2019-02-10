<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser;

use Innmind\Crawler\{
    Parser\AlternatesParser,
    Parser\Http\AlternatesParser as HttpParser,
    Parser\Http\ContentTypeParser,
    Parser\HtmlParser,
    Parser\Html,
    Parser,
    HttpResource\Alternates,
    HttpResource\Attribute,
    UrlResolver,
};
use Innmind\UrlResolver\UrlResolver as BaseResolver;
use Innmind\Http\{
    Message\Response,
    Message\Request\Request,
    Message\Method\Method,
    Headers\Headers,
    ProtocolVersion\ProtocolVersion,
    Header,
    Header\Link,
    Header\LinkValue,
    Header\Parameter,
};
use Innmind\Url\Url;
use Innmind\Filesystem\{
    Stream\StringStream,
    MediaType\MediaType,
};
use Innmind\Immutable\Map;
use function Innmind\Html\bootstrap as html;
use PHPUnit\Framework\TestCase;

class AlternatesParserTest extends TestCase
{
    private $parse;

    public function setUp(): void
    {
        $this->parse = new AlternatesParser(
            new HttpParser(
                $resolver = new UrlResolver(new BaseResolver)
            ),
            new HtmlParser(
                new Html\AlternatesParser(
                    html(),
                    $resolver
                )
            )
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Parser::class,
            $this->parse
        );
    }

    public function testParseFromHeaders()
    {
        $response = $this->createMock(Response::class);
        $response
            ->method('headers')
            ->willReturn(
                Headers::of(
                    new Link(
                        new LinkValue(
                            Url::fromString('/en/foo/bar'),
                            'alternate',
                            Map::of('string', Parameter::class)
                                (
                                    'hreflang',
                                    new Parameter\Parameter('hreflang', 'en')
                                )
                        )
                    )
                )
            );

        $attributes = ($this->parse)(
            new Request(
                Url::fromString('http://example.com/foo/'),
                new Method('GET'),
                new ProtocolVersion(1, 1)
            ),
            $response,
            new Map('string', Attribute::class)
        );

        $this->assertTrue($attributes->contains('alternates'));
        $alternates = $attributes->get('alternates');
        $this->assertInstanceOf(Alternates::class, $alternates);
        $this->assertCount(1, $alternates->content());
        $this->assertTrue($alternates->content()->contains('en'));
    }

    public function testParseFromHtml()
    {
        $response = $this->createMock(Response::class);
        $response
            ->method('body')
            ->willReturn(
                new StringStream(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <link rel="alternate" hreflang="fr" href="/fr/" />
</head>
<body>
</body>
</html>
HTML
                )
            );

        $attributes = Map::of('string', Attribute::class)
            (
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
                )
            );

        $attributes = ($this->parse)(
            new Request(
                Url::fromString('http://example.com/foo/'),
                new Method('GET'),
                new ProtocolVersion(1, 1)
            ),
            $response,
            $attributes
        );

        $this->assertTrue($attributes->contains('alternates'));
        $alternates = $attributes->get('alternates');
        $this->assertInstanceOf(Alternates::class, $alternates);
        $content = $alternates->content();
        $this->assertCount(1, $content);
        $this->assertTrue($content->contains('fr'));
    }

    public function testMergeResults()
    {
        $response = $this->createMock(Response::class);
        $response
            ->method('headers')
            ->willReturn(
                Headers::of(
                    new Link(
                        new LinkValue(
                            Url::fromString('/en/foo/bar'),
                            'alternate',
                            Map::of('string', Parameter::class)
                                (
                                    'hreflang',
                                    new Parameter\Parameter('hreflang', 'en')
                                )
                        )
                    )
                )
            );
        $response
            ->method('body')
            ->willReturn(
                new StringStream(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <link rel="alternate" hreflang="fr" href="/fr/" />
</head>
<body>
</body>
</html>
HTML
                )
            );
        $attributes = (new Map('string', Attribute::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
                )
            );

        $attributes = ($this->parse)(
            new Request(
                Url::fromString('http://example.com/foo/'),
                new Method('GET'),
                new ProtocolVersion(1, 1)
            ),
            $response,
            $attributes
        );

        $this->assertTrue($attributes->contains('alternates'));
        $alternates = $attributes->get('alternates');
        $this->assertInstanceOf(Alternates::class, $alternates);
        $this->assertCount(2, $alternates->content());
        $this->assertTrue($alternates->content()->contains('en'));
        $this->assertTrue($alternates->content()->contains('fr'));
    }
}
