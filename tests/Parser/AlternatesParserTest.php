<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser;

use Innmind\Crawler\{
    Parser\AlternatesParser,
    Parser\Http\AlternatesParser as HttpParser,
    Parser\Http\ContentTypeParser,
    Parser\Html\AlternatesParser as HtmlParser,
    ParserInterface,
    HttpResource\AttributeInterface,
    HttpResource\Alternates,
    HttpResource\Attribute
};
use Innmind\UrlResolver\UrlResolver;
use Innmind\TimeContinuum\TimeContinuum\Earth;
use Innmind\Html\{
    Reader\Reader,
    Translator\NodeTranslators as HtmlTranslators
};
use Innmind\Xml\Translator\{
    NodeTranslator,
    NodeTranslators
};
use Innmind\Http\{
    Message\ResponseInterface,
    Message\Request,
    Message\Method,
    Headers,
    ProtocolVersion,
    Header\HeaderInterface,
    Header\HeaderValueInterface,
    Header\Link,
    Header\LinkValue,
    Header\ParameterInterface,
    Header\Parameter
};
use Innmind\Url\Url;
use Innmind\Filesystem\{
    Stream\StringStream,
    MediaType\MediaType
};
use Innmind\Immutable\{
    Map,
    Set
};

class AlternatesParserTest extends \PHPUnit_Framework_TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new AlternatesParser(
            new HttpParser(
                $resolver = new UrlResolver,
                $clock = new Earth
            ),
            new HtmlParser(
                new Reader(
                    new NodeTranslator(
                        NodeTranslators::defaults()->merge(
                            HtmlTranslators::defaults()
                        )
                    )
                ),
                $clock,
                $resolver
            )
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            ParserInterface::class,
            $this->parser
        );
    }

    public function testParseFromHeaders()
    {
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

        $attributes = $this->parser->parse(
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
        $this->assertInstanceOf(Alternates::class, $alternates);
        $this->assertCount(1, $alternates->content());
        $this->assertTrue($alternates->content()->contains('en'));
    }

    public function testParseFromHtml()
    {
        $response = $this->createMock(ResponseInterface::class);
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

        $attributes = (new Map('string', AttributeInterface::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html'),
                    0
                )
            );

        $attributes = $this->parser->parse(
            new Request(
                Url::fromString('http://example.com/foo/'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers(new Map('string', HeaderInterface::class)),
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
        $attributes = (new Map('string', AttributeInterface::class))
            ->put(
                ContentTypeParser::key(),
                new Attribute(
                    ContentTypeParser::key(),
                    MediaType::fromString('text/html'),
                    0
                )
            );

        $attributes = $this->parser->parse(
            new Request(
                Url::fromString('http://example.com/foo/'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers(new Map('string', HeaderInterface::class)),
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
