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
    Header\ContentType,
    Header\ContentTypeValue,
};
use Innmind\Immutable\Map;
use function Innmind\Immutable\first;

final class CharsetParser implements Parser
{
    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        if (!$response->headers()->contains('Content-Type')) {
            return $attributes;
        }

        $header = $response->headers()->get('Content-Type');

        if (!$header instanceof ContentType) {
            return $attributes;
        }

        /** @var ContentTypeValue $value */
        $value = first($header->values());

        if (!$value->parameters()->contains('charset')) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                $value
                    ->parameters()
                    ->get('charset')
                    ->value()
            )
        );
    }

    public static function key(): string
    {
        return 'charset';
    }
}
