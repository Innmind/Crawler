<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute,
    UrlResolver
};
use Innmind\TimeContinuum\TimeContinuumInterface;
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
                ),
                $this
                    ->clock
                    ->now()
                    ->elapsedSince($start)
                    ->milliseconds()
            )
        );
    }

    public static function key(): string
    {
        return 'canonical';
    }
}
