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
use Innmind\Immutable\{
    MapInterface,
    Map,
};

final class DimensionParser implements Parser
{
    public function __invoke(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        $infos = \getimagesizefromstring(
            (string) $response->body()
        );

        return $attributes->put(
            self::key(),
            new Attributes(
                self::key(),
                Map::of('string', Attribute::class)
                    ('width', new Attribute\Attribute('width', $infos[0]))
                    ('height', new Attribute\Attribute('height', $infos[1]))
            )
        );
    }

    public static function key(): string
    {
        return 'dimension';
    }
}
