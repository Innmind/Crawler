<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Image;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\MapInterface;

final class WeightParser implements Parser
{
    use ImageTrait;

    public function parse(
        Request $request,
        Response $response,
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
