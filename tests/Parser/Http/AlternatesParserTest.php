<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser\Http\AlternatesParser,
    HttpResource\AttributeInterface,
    HttpResource\Attributes,
    ParserInterface
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PointInTimeInterface,
    ElapsedPeriod
};
use Innmind\UrlResolver\UrlResolver;
use Innmind\Url\Url;
use Innmind\Http\{
    Message\Request,
    Message\ResponseInterface,
    Message\Method,
    Headers,
    ProtocolVersion,
    Header\HeaderInterface,
    Header\Header,
    Header\HeaderValue,
    Header\HeaderValueInterface,
    Header\ParameterInterface,
    Header\Parameter,
    Header\Link,
    Header\LinkValue
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    Map,
    Set,
    SetInterface
};

class AlternatesParserTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $parser = new AlternatesParser(
            new UrlResolver,
            $this->createMock(TimeContinuumInterface::class)
        );

        $this->assertInstanceOf(ParserInterface::class, $parser);
    }

    public function testKey()
    {
        $this->assertSame('alternates', AlternatesParser::key());
    }

    public function testParseWhenNoLink()
    {
        $parser = new AlternatesParser(
            new UrlResolver,
            $this->createMock(TimeContinuumInterface::class)
        );

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('headers')
            ->willReturn(
                new Headers(
                    new Map('string', HeaderInterface::class)
                )
            );
        $attributes = $parser->parse(
            new Request(
                Url::fromString('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers(new Map('string', HeaderInterface::class)),
                new StringStream('')
            ),
            $response,
            $expected = new Map('string', AttributeInterface::class)
        );

        $this->assertSame($expected, $attributes);
    }

    public function testParseWhenLinkNotACorrectlyParsedOne()
    {
        $parser = new AlternatesParser(
            new UrlResolver,
            $this->createMock(TimeContinuumInterface::class)
        );

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'link',
                            new Header(
                                'Link',
                                (new Set(HeaderValueInterface::class))
                                    ->add(new HeaderValue('</foo/bar>; rel="index"'))
                            )
                        )
                )
            );
        $attributes = $parser->parse(
            new Request(
                Url::fromString('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers(new Map('string', HeaderInterface::class)),
                new StringStream('')
            ),
            $response,
            $expected = new Map('string', AttributeInterface::class)
        );

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $parser = new AlternatesParser(
            new UrlResolver,
            $clock = $this->createMock(TimeContinuumInterface::class)
        );

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'link',
                            new Link(
                                (new Set(HeaderValueInterface::class))
                                    ->add(new LinkValue(
                                        Url::fromString('/foo/bar'),
                                        'alternate',
                                        (new Map('string', ParameterInterface::class))
                                            ->put(
                                                'hreflang',
                                                new Parameter('hreflang', 'fr')
                                            )
                                    ))
                                    ->add(new LinkValue(
                                        Url::fromString('bar'),
                                        'alternate',
                                        (new Map('string', ParameterInterface::class))
                                            ->put(
                                                'hreflang',
                                                new Parameter('hreflang', 'fr')
                                            )
                                    ))
                                    ->add(new LinkValue(
                                        Url::fromString('baz'),
                                        'alternate',
                                        (new Map('string', ParameterInterface::class))
                                            ->put(
                                                'hreflang',
                                                new Parameter('hreflang', 'fr')
                                            )
                                    ))
                                    ->add(new LinkValue(
                                        Url::fromString('/en/foo/bar'),
                                        'alternate',
                                        (new Map('string', ParameterInterface::class))
                                            ->put(
                                                'hreflang',
                                                new Parameter('hreflang', 'en')
                                            )
                                    ))
                            )
                        )
                )
            );
        $clock
            ->expects($this->exactly(3))
            ->method('now')
            ->will(
                $this->onConsecutiveCalls(
                    $start = $this->createMock(PointInTimeInterface::class),
                    $end1 = $this->createMock(PointInTimeInterface::class),
                    $end2 = $this->createMock(PointInTimeInterface::class)
                )
            );
        $end1
            ->expects($this->once())
            ->method('elapsedSince')
            ->with($start)
            ->willReturn(new ElapsedPeriod(24));
        $end2
            ->expects($this->once())
            ->method('elapsedSince')
            ->with($start)
            ->willReturn(new ElapsedPeriod(42));
        $attributes = $parser->parse(
            new Request(
                Url::fromString('http://example.com/foo/'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers(new Map('string', HeaderInterface::class)),
                new StringStream('')
            ),
            $response,
            new Map('string', AttributeInterface::class)
        );

        $this->assertTrue($attributes->contains('alternates'));
        $alternates = $attributes->get('alternates');
        $this->assertInstanceOf(Attributes::class, $alternates);
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
            ['http://example.com/en/foo/bar'],
            $content->get('en')->content()->toPrimitive()
        );
        $this->assertSame(42, $content->get('en')->parsingTime());
        $this->assertSame(
            ['http://example.com/foo/bar', 'http://example.com/foo/baz'],
            $content->get('fr')->content()->toPrimitive()
        );
        $this->assertSame(24, $content->get('fr')->parsingTime());
    }
}
