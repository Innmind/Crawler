<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
    UrlResolver,
};
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Header\Link,
    Header\LinkValue,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\first;

final class CanonicalParser implements Parser
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

        /**
         * @psalm-suppress ArgumentTypeCoercion We verify above we do have a link
         * @var Set<LinkValue>
         */
        $links = $response
            ->headers()
            ->get('Link')
            ->values()
            ->filter(static function(LinkValue $value): bool {
                return $value->relationship() === 'canonical';
            });

        if ($links->size() !== 1) {
            return $attributes;
        }

        return ($attributes)(
            self::key(),
            new Attribute(
                self::key(),
                ($this->resolve)(
                    $request,
                    $attributes,
                    first($links)->url(),
                ),
            ),
        );
    }

    public static function key(): string
    {
        return 'canonical';
    }
}
