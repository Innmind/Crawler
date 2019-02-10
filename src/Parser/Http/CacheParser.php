<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
};
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Header\Value,
    Header\CacheControlValue\SharedMaxAge,
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    Period\Earth\Second,
};
use Innmind\Immutable\MapInterface;

final class CacheParser implements Parser
{
    private $clock;

    public function __construct(TimeContinuumInterface $clock)
    {
        $this->clock = $clock;
    }

    public function __invoke(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        if (!$response->headers()->has('Cache-Control')) {
            return $attributes;
        }

        $directives = $response
            ->headers()
            ->get('Cache-Control')
            ->values()
            ->filter(static function(Value $value): bool {
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
