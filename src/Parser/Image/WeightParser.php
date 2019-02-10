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
    public function __invoke(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        if (!$response->body()->knowsSize()) {
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
