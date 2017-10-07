<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute
};
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Header\ContentType
};
use Innmind\Filesystem\MediaType\MediaType;
use Innmind\Immutable\MapInterface;

final class ContentTypeParser implements Parser
{
    public function parse(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        if (
            !$response->headers()->has('Content-Type') ||
            !$response->headers()->get('Content-Type') instanceof ContentType
        ) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                MediaType::fromString(
                    (string) $response
                        ->headers()
                        ->get('Content-Type')
                        ->values()
                        ->current()
                )
            )
        );
    }

    public static function key(): string
    {
        return 'content_type';
    }
}
