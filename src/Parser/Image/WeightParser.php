<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Image;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute
};
use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Immutable\MapInterface;

final class WeightParser implements ParserInterface
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

        if (
            !$this->isImage($attributes) ||
            !$response->body()->knowsSize()
        ) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                $response->body()->size(),
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
        return 'weight';
    }
}
