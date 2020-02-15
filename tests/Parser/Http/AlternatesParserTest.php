<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser\Http\AlternatesParser,
    HttpResource\Attribute,
    HttpResource\Alternates,
    Parser,
    UrlResolver,
};
use Innmind\UrlResolver\UrlResolver as BaseResolver;
use Innmind\Url\Url;
use Innmind\Http\{
    Message\Request\Request,
    Message\Response,
    Message\Method,
    Headers,
    ProtocolVersion,
    Header,
    Header\Value\Value,
    Header\Parameter,
    Header\Link,
    Header\LinkValue,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\{
    first,
    unwrap,
};
use PHPUnit\Framework\TestCase;

class AlternatesParserTest extends TestCase
{
    private $parse;

    public function setUp(): void
    {
        $this->parse = new AlternatesParser(
            new UrlResolver(new BaseResolver)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(Parser::class, $this->parse);
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
        $attributes = ($this->parse)(
            new Request(
                Url::of('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1)
            ),
            $response,
            $expected = Map::of('string', Attribute::class)
        );

        $this->assertSame($expected, $attributes);
    }

    public function testParseWhenLinkNotACorrectlyParsedOne()
    {
        $response = $this->createMock(Response::class);
        $response
            ->method('headers')
            ->willReturn(
                Headers::of(
                    new Header\Header(
                        'Link',
                        new Value('</foo/bar>; rel="index"')
                    )
                )
            );
        $attributes = ($this->parse)(
            new Request(
                Url::of('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1)
            ),
            $response,
            $expected = Map::of('string', Attribute::class)
        );

        $this->assertSame($expected, $attributes);
    }

    public function testParseWhenNoAlternate()
    {
        $response = $this->createMock(Response::class);
        $response
            ->method('headers')
            ->willReturn(
                Headers::of(
                    new Link(
                        new LinkValue(
                            Url::of('/foo/bar'),
                            'prev'
                        )
                    )
                )
            );
        $attributes = ($this->parse)(
            new Request(
                Url::of('http://example.com/foo/'),
                new Method('GET'),
                new ProtocolVersion(1, 1)
            ),
            $response,
            $expected = Map::of('string', Attribute::class)
        );

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $response = $this->createMock(Response::class);
        $response
            ->method('headers')
            ->willReturn(
                Headers::of(
                    new Link(
                        new LinkValue(
                            Url::of('/foo/bar'),
                            'alternate',
                            new Parameter\Parameter('hreflang', 'fr')
                        ),
                        new LinkValue(
                            Url::of('bar'),
                            'alternate',
                            new Parameter\Parameter('hreflang', 'fr')
                        ),
                        new LinkValue(
                            Url::of('baz'),
                            'alternate',
                            new Parameter\Parameter('hreflang', 'fr')
                        ),
                        new LinkValue(
                            Url::of('/en/foo/bar'),
                            'alternate',
                            new Parameter\Parameter('hreflang', 'en')
                        )
                    )
                )
            );
        $attributes = ($this->parse)(
            new Request(
                Url::of('http://example.com/foo/'),
                new Method('GET'),
                new ProtocolVersion(1, 1)
            ),
            $response,
            Map::of('string', Attribute::class)
        );

        $this->assertTrue($attributes->contains('alternates'));
        $alternates = $attributes->get('alternates');
        $this->assertInstanceOf(Alternates::class, $alternates);
        $content = $alternates->content();
        $this->assertCount(2, $content);
        $this->assertTrue($content->contains('en'));
        $this->assertTrue($content->contains('fr'));
        $this->assertInstanceOf(
            Set::class,
            $content->get('en')->content()
        );
        $this->assertInstanceOf(
            Set::class,
            $content->get('fr')->content()
        );
        $this->assertSame(
            Url::class,
            (string) $content->get('en')->content()->type()
        );
        $this->assertSame(
            Url::class,
            (string) $content->get('fr')->content()->type()
        );
        $this->assertSame(
            'http://example.com/en/foo/bar',
            first($content->get('en')->content())->toString(),
        );
        $fr = unwrap($content->get('fr')->content());
        $this->assertSame(
            'http://example.com/foo/bar',
            \current($fr)->toString(),
        );
        \next($fr);
        $this->assertSame(
            'http://example.com/foo/baz',
            \current($fr)->toString(),
        );
    }
}
