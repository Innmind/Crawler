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
use Innmind\TimeContinuum\TimeContinuumInterface;
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
    private $clock;
    private $resolver;

    public function __construct(
        ReaderInterface $reader,
        TimeContinuumInterface $clock,
        UrlResolver $resolver
    ) {
        $this->reader = $reader;
        $this->clock = $clock;
        $this->resolver = $resolver;
    }

    public function parse(
        RequestInterface $request,
        ResponseInterface $response,
        MapInterface $attributes
    ): MapInterface {
        $start = $this->clock->now();

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
            ->map(function(string $language, SequenceInterface $links) use ($request, $attributes): SequenceInterface {
                return $links->map(function(Pair $link) use ($request, $attributes): string {
                    return (string) $this->resolver->resolve(
                        $request,
                        $attributes,
                        $link->key()
                    );
                });
            })
            ->reduce(
                new Map('string', AttributeInterface::class),
                function(Map $languages, string $language, SequenceInterface $links) use ($start): Map {
                    return $languages->put(
                        $language,
                        new Alternate(
                            $language,
                            $links->reduce(
                                new Set('string'),
                                function(Set $links, string $link): Set {
                                    return $links->add($link);
                                }
                            ),
                            $this
                                ->clock
                                ->now()
                                ->elapsedSince($start)
                                ->milliseconds()
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
