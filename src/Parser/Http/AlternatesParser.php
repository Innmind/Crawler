<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\AttributeInterface,
    HttpResource\Attribute,
    HttpResource\Attributes
};
use Innmind\UrlResolver\ResolverInterface;
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

    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    public function parse(
        RequestInterface $request,
        ResponseInterface $response,
        MapInterface $attributes
    ): MapInterface {
        $start = (int) round(microtime(true) * 1000);

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
                function (Map $links, LinkValue $header): Map {
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
            ->groupBy(function (UrlInterface $url, string $language): string {
                return $language;
            })
            ->map(function (string $language, SequenceInterface $links) use ($request): SequenceInterface {
                return $links->map(function (Pair $link) use ($request): string {
                    return $this->resolver->resolve(
                        (string) $request->url(),
                        (string) $link->key()
                    );
                });
            })
            ->reduce(
                new Map('string', AttributeInterface::class),
                function (Map $languages, string $language, SequenceInterface $links) use ($start): Map {
                    return $languages->put(
                        $language,
                        new Attribute(
                            $language,
                            $links->reduce(
                                new Set('string'),
                                function (Set $links, string $link): Set {
                                    return $links->add($link);
                                }
                            ),
                            (int) round(microtime(true) * 1000) - $start
                        )
                    );
                }
            );

        return $attributes->put(
            'alternates',
            new Attributes('alternates', $alternates)
        );
    }
}
