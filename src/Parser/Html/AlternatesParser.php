<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\AttributeInterface,
    HttpResource\Alternate,
    HttpResource\Alternates,
    UrlResolver
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Head,
    Element\Link,
    Exception\ElementNotFoundException
};
use Innmind\Xml\{
    ReaderInterface,
    NodeInterface
};
use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    MapInterface,
    Map,
    SequenceInterface,
    Pair,
    Set
};

final class AlternatesParser implements ParserInterface
{
    use HtmlTrait;

    private $reader;
    private $resolver;

    public function __construct(
        ReaderInterface $reader,
        UrlResolver $resolver
    ) {
        $this->reader = $reader;
        $this->resolver = $resolver;
    }

    public function parse(
        RequestInterface $request,
        ResponseInterface $response,
        MapInterface $attributes
    ): MapInterface {
        if (!$this->isHtml($attributes)) {
            return $attributes;
        }

        $document = $this->reader->read($response->body());

        try {
            $links = (new Elements('link'))(
                (new Head)($document)
            );
        } catch (ElementNotFoundException $e) {
            return $attributes;
        }

        $links = $links
            ->filter(function(NodeInterface $link): bool {
                return $link instanceof Link;
            })
            ->filter(function(Link $link): bool {
                return $link->relationship() === 'alternate' &&
                    $link->attributes()->contains('hreflang');
            });

        if ($links->size() === 0) {
            return $attributes;
        }

        $alternates = $links
            ->reduce(
                new Map(UrlInterface::class, 'string'),
                function(Map $links, Link $link): Map {
                    return $links->put(
                        $link->href(),
                        $link->attributes()->get('hreflang')->value()
                    );
                }
            )
            ->groupBy(function(UrlInterface $url, string $language) {
                return $language;
            })
            ->map(function(string $language, MapInterface $links) use ($request, $attributes): MapInterface {
                return $links->map(function(UrlInterface $link, string $language) use ($request, $attributes): Pair {
                    $link = $this->resolver->resolve(
                        $request,
                        $attributes,
                        $link
                    );

                    return new Pair($link, $language);
                });
            })
            ->reduce(
                new Map('string', AttributeInterface::class),
                function(Map $languages, string $language, MapInterface $links): Map {
                    return $languages->put(
                        $language,
                        new Alternate(
                            $language,
                            $links->keys()->reduce(
                                new Set(UrlInterface::class),
                                function(Set $links, UrlInterface $link): Set {
                                    return $links->add($link);
                                }
                            )
                        )
                    );
                }
            );

        return $attributes->put(
            self::key(),
            new Alternates($alternates)
        );
    }

    public static function key(): string
    {
        return 'alternates';
    }
}
