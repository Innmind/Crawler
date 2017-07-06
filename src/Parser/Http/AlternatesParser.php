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

    public function __construct(UrlResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function parse(
        RequestInterface $request,
        ResponseInterface $response,
        MapInterface $attributes
    ): MapInterface {
        if (
            !$response->headers()->has('Link') ||
            !$response->headers()->get('Link') instanceof Link
        ) {
            return $attributes;
        }

        $links = $response
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
            );

        if ($links->size() === 0) {
            return $attributes;
        }

        $alternates = $links
            ->groupBy(function(UrlInterface $url, string $language): string {
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
