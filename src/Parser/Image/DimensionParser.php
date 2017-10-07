<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Image;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attributes\Attributes,
    HttpResource\Attribute
};
use Innmind\Http\Message\{
    Request,
    Response
};
use Innmind\Immutable\{
    MapInterface,
    Map
};

final class DimensionParser implements Parser
{
    use ImageTrait;

    public function parse(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        if (!$this->isImage($attributes)) {
            return $attributes;
        }

        $infos = getimagesizefromstring(
            (string) $response->body()
        );

        return $attributes->put(
            self::key(),
            new Attributes(
                self::key(),
                (new Map('string', Attribute::class))
                    ->put(
                        'width',
                        new Attribute\Attribute(
                            'width',
                            $infos[0]
                        )
                    )
                    ->put(
                        'height',
                        new Attribute\Attribute(
                            'height',
                            $infos[1]
                        )
                    )
            )
        );
    }

    public static function key(): string
    {
        return 'dimension';
    }
}
