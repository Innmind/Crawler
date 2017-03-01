<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute,
    UrlResolver
};
use Innmind\Http\{
    Message\RequestInterface,
    Message\ResponseInterface,
    Header\Link,
    Header\LinkValue
};
use Innmind\Immutable\MapInterface;

final class CanonicalParser implements ParserInterface
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
