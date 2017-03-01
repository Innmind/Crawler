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
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    Period\Earth\Second
};
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
            self::key(),
            new Attribute(
                self::key(),
                $this
                    ->clock
                    ->now()
                    ->goForward(
                        new Second($directives->current()->age())
                    )
            )
        );
    }

    public static function key(): string
    {
        return 'expires_at';
    }
}
