<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Image;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute,
    HttpResource\Attributes,
    HttpResource\AttributeInterface
};
use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Innmind\Immutable\{
    MapInterface,
    Map
};

final class DimensionParser implements ParserInterface
{
    use ImageTrait;

    public function parse(
        RequestInterface $request,
        ResponseInterface $response,
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
                (new Map('string', AttributeInterface::class))
                    ->put(
                        'width',
                        new Attribute(
                            'width',
                            $infos[0]
                        )
                    )
                    ->put(
                        'height',
                        new Attribute(
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
