<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute,
    HttpResource\Alternate,
    HttpResource\Alternates,
    UrlResolver,
};
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Header\Link,
    Header\LinkValue,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    MapInterface,
    Map,
    Pair,
};

final class AlternatesParser implements Parser
{
    private $resolver;

    public function __construct(UrlResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function parse(
        Request $request,
        Response $response,
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
                static function(MapInterface $links, LinkValue $header): MapInterface {
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
            ->groupBy(static function(UrlInterface $url, string $language): string {
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
                new Map('string', Attribute::class),
                static function(MapInterface $languages, string $language, MapInterface $links): MapInterface {
                    return $languages->put(
                        $language,
                        new Alternate(
                            $language,
                            $links->keys()
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
