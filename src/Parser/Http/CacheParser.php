<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute
};
use Innmind\Http\{
    Message\RequestInterface,
    Message\ResponseInterface,
    Header\HeaderValueInterface,
    Header\CacheControlValue\SharedMaxAge
};
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Immutable\MapInterface;

final class CacheParser implements ParserInterface
{
    private $clock;

    public function __construct(TimeContinuumInterface $clock)
    {
        $this->clock = $clock;
    }

    public function parse(
        RequestInterface $request,
        ResponseInterface $response,
        MapInterface $attributes
    ): MapInterface {
        $start = $this->clock->now();

        if (!$response->headers()->has('Cache-Control')) {
            return $attributes;
        }

        $directives = $response
            ->headers()
            ->get('Cache-Control')
            ->values()
            ->filter(function(HeaderValueInterface $value): bool {
                return $value instanceof SharedMaxAge;
            });

        if ($directives->size() !== 1) {
            return $attributes;
        }

        return $attributes->put(
            'expires_at',
            new Attribute(
                'expires_at',
                (new \DateTimeImmutable)->modify(
                    sprintf(
                        '+%s seconds',
                        $directives->current()->age()
                    )
                ),
                $this
                    ->clock
                    ->now()
                    ->elapsedSince($start)
                    ->milliseconds()
            )
        );
    }
}
