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
use Innmind\Url\Url;
use Innmind\Immutable\{
    Map,
    Pair,
};

final class AlternatesParser implements Parser
{
    private UrlResolver $resolve;

    public function __construct(UrlResolver $resolve)
    {
        $this->resolve = $resolve;
    }

    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        if (
            !$response->headers()->contains('Link') ||
            !$response->headers()->get('Link') instanceof Link
        ) {
            return $attributes;
        }

        /** @psalm-suppress ArgumentTypeCoercion we verify above we do have a Link */
        $links = $response
            ->headers()
            ->get('Link')
            ->values()
            ->reduce(
                Map::of(Url::class, 'string'),
                static function(Map $links, LinkValue $header): Map {
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

        /** @var Map<string, Attribute> */
        $alternates = $links
            ->groupBy(static function(Url $url, string $language): string {
                return $language;
            })
            ->map(function(string $language, Map $links) use ($request, $attributes): Map {
                return $links->map(function(Url $link, string $language) use ($request, $attributes): Pair {
                    $link = ($this->resolve)(
                        $request,
                        $attributes,
                        $link
                    );

                    return new Pair($link, $language);
                });
            })
            ->reduce(
                Map::of('string', Attribute::class),
                static function(Map $languages, string $language, Map $links): Map {
                    /** @var Map<Url, string> $links */

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
