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
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Immutable\{
    MapInterface,
    Map
};

final class DimensionParser implements ParserInterface
{
    use ImageTrait;

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

        if (!$this->isImage($attributes)) {
            return $attributes;
        }

        $infos = getimagesizefromstring(
            (string) $response->body()
        );

        $time = $this
            ->clock
            ->now()
            ->elapsedSince($start)
            ->milliseconds();

        return $attributes->put(
            self::key(),
            new Attributes(
                self::key(),
                (new Map('string', AttributeInterface::class))
                    ->put(
                        'width',
                        new Attribute(
                            'width',
                            $infos[0],
                            $time
                        )
                    )
                    ->put(
                        'height',
                        new Attribute(
                            'height',
                            $infos[1],
                            $time
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
