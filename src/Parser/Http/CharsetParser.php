<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute
};
use Innmind\Http\{
    Message\RequestInterface,
    Message\ResponseInterface,
    Header\ContentType
};
use Innmind\Immutable\MapInterface;

final class CharsetParser implements ParserInterface
{
    public function parse(
        RequestInterface $request,
        ResponseInterface $response,
        MapInterface $attributes
    ): MapInterface {
        if (
            !$response->headers()->has('Content-Type') ||
            !($header = $response->headers()->get('Content-Type')) instanceof ContentType ||
            !$header->values()->current()->parameters()->contains('charset')
        ) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                $header
                    ->values()
                    ->current()
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
