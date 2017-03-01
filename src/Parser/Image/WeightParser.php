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
use Innmind\Immutable\MapInterface;

final class WeightParser implements ParserInterface
{
    use ImageTrait;

    public function parse(
        RequestInterface $request,
        ResponseInterface $response,
        MapInterface $attributes
    ): MapInterface {
        if (
            !$this->isImage($attributes) ||
            !$response->body()->knowsSize()
        ) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), $response->body()->size())
        );
    }

    public static function key(): string
    {
        return 'weight';
    }
}
