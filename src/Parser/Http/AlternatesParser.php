<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\AttributeInterface,
    HttpResource\Alternate,
    HttpResource\Alternates,
    UrlResolver
};
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Http\{
    Message\RequestInterface,
    Message\ResponseInterface,
    Header\Link,
    Header\LinkValue
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    MapInterface,
    Map,
    SequenceInterface,
    Set,
    Pair
};

final class AlternatesParser implements ParserInterface
{
    private $resolver;
    private $clock;

    public function __construct(
        UrlResolver $resolver,
        TimeContinuumInterface $clock
    ) {
        $this->resolver = $resolver;
        $this->clock = $clock;
    }

    public function parse(
        RequestInterface $request,
        ResponseInterface $response,
        MapInterface $attributes
    ): MapInterface {
        $start = $this->clock->now();

        if (
            !$response->headers()->has('Link') ||
            !$response->headers()->get('Link') instanceof Link
        ) {
            return $attributes;
        }

        $alternates = $response
            ->headers()
            ->get('Link')
            ->values()
            ->reduce(
                new Map(UrlInterface::class, 'string'),
                function(Map $links, LinkValue $header): Map {
                    if (
                        $header->relationship() !== 'alternate' ||
                        !$header->parameters()->contains('hreflang')
                    ) {
                        return $links;
                    }

                    return $links->put(
                        $header->url(),
                        $header->parameters()->get('hreflang')->value()
                    );
                }
            )
            ->groupBy(function(UrlInterface $url, string $language): string {
                return $language;
            })
            ->map(function(string $language, SequenceInterface $links) use ($request, $attributes): SequenceInterface {
                return $links->map(function(Pair $link) use ($request, $attributes): UrlInterface {
                    return $this->resolver->resolve(
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
                                new Set(UrlInterface::class),
                                function(Set $links, UrlInterface $link): Set {
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
