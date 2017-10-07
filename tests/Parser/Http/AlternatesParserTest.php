<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser\Http\AlternatesParser,
    HttpResource\Attribute,
    HttpResource\Alternates,
    Parser,
    UrlResolver
};
use Innmind\UrlResolver\UrlResolver as BaseResolver;
use Innmind\Url\{
    Url,
    UrlInterface
};
use Innmind\Http\{
    Message\Request\Request,
    Message\Response,
    Message\Method\Method,
    Headers\Headers,
    ProtocolVersion\ProtocolVersion,
    Header,
    Header\Value\Value,
    Header\Parameter,
    Header\Link,
    Header\LinkValue
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    Map,
    SetInterface
};
use PHPUnit\Framework\TestCase;

class AlternatesParserTest extends TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new AlternatesParser(
            new UrlResolver(new BaseResolver)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(Parser::class, $this->parser);
    }

    public function testKey()
    {
        $this->assertSame('alternates', AlternatesParser::key());
    }

    public function testParseWhenNoLink()
    {
        $response = $this->createMock(Response::class);
        $response
            ->method('headers')
            ->willReturn(new Headers);
        $attributes = $this->parser->parse(
            new Request(
                Url::fromString('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers,
                new StringStream('')
            ),
            $response,
            $expected = new Map('string', Attribute::class)
        );

        $this->assertSame($expected, $attributes);
    }

    public function testParseWhenLinkNotACorrectlyParsedOne()
    {
        $response = $this->createMock(Response::class);
        $response
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', Header::class))
                        ->put(
                            'link',
                            new Header\Header(
                                'Link',
                                new Value('</foo/bar>; rel="index"')
                            )
                        )
                )
            );
        $attributes = $this->parser->parse(
            new Request(
                Url::fromString('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers,
                new StringStream('')
            ),
            $response,
            $expected = new Map('string', Attribute::class)
        );

        $this->assertSame($expected, $attributes);
    }

    public function testParseWhenNoAlternate()
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
                                    Url::fromString('/foo/bar'),
                                    'prev'
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
            $expected = new Map('string', Attribute::class)
        );

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
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
                                    Url::fromString('/foo/bar'),
                                    'alternate',
                                    (new Map('string', Parameter::class))
                                        ->put(
                                            'hreflang',
                                            new Parameter\Parameter('hreflang', 'fr')
                                        )
                                ),
                                new LinkValue(
                                    Url::fromString('bar'),
                                    'alternate',
                                    (new Map('string', Parameter::class))
                                        ->put(
                                            'hreflang',
                                            new Parameter\Parameter('hreflang', 'fr')
                                        )
                                ),
                                new LinkValue(
                                    Url::fromString('baz'),
                                    'alternate',
                                    (new Map('string', Parameter::class))
                                        ->put(
                                            'hreflang',
                                            new Parameter\Parameter('hreflang', 'fr')
                                        )
                                ),
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
        $content = $alternates->content();
        $this->assertCount(2, $content);
        $this->assertTrue($content->contains('en'));
        $this->assertTrue($content->contains('fr'));
        $this->assertInstanceOf(
            SetInterface::class,
            $content->get('en')->content()
        );
        $this->assertInstanceOf(
            SetInterface::class,
            $content->get('fr')->content()
        );
        $this->assertSame(
            UrlInterface::class,
            (string) $content->get('en')->content()->type()
        );
        $this->assertSame(
            UrlInterface::class,
            (string) $content->get('fr')->content()->type()
        );
        $this->assertSame(
            'http://example.com/en/foo/bar',
            (string) $content->get('en')->content()->current()
        );
        $this->assertSame(
            'http://example.com/foo/bar',
            (string) $content->get('fr')->content()->current()
        );
        $content->get('fr')->content()->next();
        $this->assertSame(
            'http://example.com/foo/baz',
            (string) $content->get('fr')->content()->current()
        );
    }
}
