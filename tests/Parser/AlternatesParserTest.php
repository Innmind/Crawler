<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser;

use Innmind\Crawler\{
    Parser\AlternatesParser,
    Parser\Http\AlternatesParser as HttpParser,
    Parser\Http\ContentTypeParser,
    Parser\Html\AlternatesParser as HtmlParser,
    Parser,
    HttpResource\Alternates,
    HttpResource\Attribute,
    UrlResolver
};
use Innmind\UrlResolver\UrlResolver as BaseResolver;
use Innmind\Html\{
    Reader\Reader,
    Translator\NodeTranslators as HtmlTranslators
};
use Innmind\Xml\Translator\{
    NodeTranslator,
    NodeTranslators
};
use Innmind\Http\{
    Message\Response,
    Message\Request\Request,
    Message\Method\Method,
    Headers\Headers,
    ProtocolVersion\ProtocolVersion,
    Header,
    Header\Link,
    Header\LinkValue,
    Header\Parameter
};
use Innmind\Url\Url;
use Innmind\Filesystem\{
    Stream\StringStream,
    MediaType\MediaType
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class AlternatesParserTest extends TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new AlternatesParser(
            new HttpParser(
                $resolver = new UrlResolver(new BaseResolver)
            ),
            new HtmlParser(
                new Reader(
                    new NodeTranslator(
                        NodeTranslators::defaults()->merge(
                            HtmlTranslators::defaults()
                        )
                    )
                ),
                $resolver
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

    public function testParseFromHeaders()
    {
        $response = $this->createMock(Response::class);
        $response
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', Header::class))
                        ->put(
                            'link',
                            new Link(
                                new LinkValue(
                                    Url::fromString('/en/foo/bar'),
                                    'alternate',
                                    (new Map('string', Parameter::class))
                                        ->put(
                                            'hreflang',
                                            new Parameter\Parameter('hreflang', 'en')
                                        )
                                )
                            )
                        )
                )
            );

        $attributes = $this->parser->parse(
            new Request(
                Url::fromString('http://example.com/foo/'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers,
                new StringStream('')
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

        $attributes = (new Map('string', Attribute::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute\Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html')
                )
            );

        $attributes = $this->parser->parse(
            new Request(
                Url::fromString('http://example.com/foo/'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers,
                new StringStream('')
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
                new Headers(
                    (new Map('string', Header::class))
                        ->put(
                            'link',
                            new Link(
                                new LinkValue(
                                    Url::fromString('/en/foo/bar'),
                                    'alternate',
                                    (new Map('string', Parameter::class))
                                        ->put(
                                            'hreflang',
                                            new Parameter\Parameter('hreflang', 'en')
                                        )
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

        $attributes = $this->parser->parse(
            new Request(
                Url::fromString('http://example.com/foo/'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers,
                new StringStream('')
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
