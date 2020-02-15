<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute,
};
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Header\ContentType,
};
use Innmind\MediaType\MediaType;
use Innmind\Immutable\Map;
use function Innmind\Immutable\first;

final class ContentTypeParser implements Parser
{
    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        if (
            !$response->headers()->contains('Content-Type') ||
            !$response->headers()->get('Content-Type') instanceof ContentType
        ) {
            return $attributes;
        }

        $contentType = first($response->headers()->get('Content-Type')->values());

        return $attributes->put(
            self::key(),
            new Attribute\Attribute(
                self::key(),
                MediaType::of($contentType->toString()),
            )
        );
    }

    public static function key(): string
    {
        return 'content_type';
    }

    /**
     * @param Map<string, Attribute> $attributes
     */
    public static function find(Map $attributes): MediaType
    {
        if (!$attributes->contains(self::key())) {
            return MediaType::null();
        }

        /** @var mixed */
        $content = $attributes->get(self::key())->content();

        if (!$content instanceof MediaType) { // in case the attribute has been overwrote
            return MediaType::null();
        }

        return $content;
    }
}
