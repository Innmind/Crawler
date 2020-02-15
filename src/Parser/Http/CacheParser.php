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
    Clock,
    Earth\Period\Second,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\first;

final class CacheParser implements Parser
{
    private Clock $clock;

    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        if (!$response->headers()->contains('Cache-Control')) {
            return $attributes;
        }

        /** @var Set<SharedMaxAge> */
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
                        new Second(first($directives)->age())
                    )
            )
        );
    }

    public static function key(): string
    {
        return 'expires_at';
    }
}
