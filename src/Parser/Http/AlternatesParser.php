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
            ->toMapOf(
                Url::class,
                'string',
                static function(LinkValue $header): \Generator {
                    if (
                        $header->relationship() === 'alternate' &&
                        $header->parameters()->contains('hreflang')
                    ) {
                        yield $header->url() => $header->parameters()->get('hreflang')->value();
                    }
                },
            );

        if ($links->empty()) {
            return $attributes;
        }

        /** @var Map<string, Attribute> */
        $alternates = $links
            ->map(function(Url $link, string $language) use ($request, $attributes): Pair {
                $link = ($this->resolve)(
                    $request,
                    $attributes,
                    $link,
                );

                return new Pair($link, $language);
            })
            ->groupBy(static function(Url $url, string $language): string {
                return $language;
            })
            ->toMapOf(
                'string',
                Attribute::class,
                static function(string $language, Map $links): \Generator {
                    /** @var Map<Url, string> $links */
                    yield $language => new Alternate(
                        $language,
                        $links->keys(),
                    );
                },
            );

        return ($attributes)(
            self::key(),
            new Alternates($alternates),
        );
    }

    public static function key(): string
    {
        return 'alternates';
    }
}
