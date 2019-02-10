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
use Innmind\Immutable\MapInterface;

final class CanonicalParser implements Parser
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
            ->filter(function(LinkValue $value): bool {
                return $value->relationship() === 'canonical';
            });

        if ($links->size() !== 1) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                $this->resolver->resolve(
                    $request,
                    $attributes,
                    $links->current()->url()
                )
            )
        );
    }

    public static function key(): string
    {
        return 'canonical';
    }
}
