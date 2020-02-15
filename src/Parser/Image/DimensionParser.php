<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Image;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attributes\Attributes,
    HttpResource\Attribute,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\Map;

final class DimensionParser implements Parser
{
    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        /** @var array{0: int, 1: int} */
        $infos = \getimagesizefromstring(
            $response->body()->toString(),
        );

        /** @var Map<string, Attribute> */
        $content = Map::of('string', Attribute::class);

        return ($attributes)(
            self::key(),
            new Attributes(
                self::key(),
                $content
                    ('width', new Attribute\Attribute('width', $infos[0]))
                    ('height', new Attribute\Attribute('height', $infos[1])),
            ),
        );
    }

    public static function key(): string
    {
        return 'dimension';
    }
}
