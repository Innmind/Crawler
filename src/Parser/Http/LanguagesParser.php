<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute
};
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Header\ContentLanguage,
    Header\ContentLanguageValue
};
use Innmind\Immutable\{
    MapInterface,
    Set
};

final class LanguagesParser implements ParserInterface
{
    public function parse(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        if (
            !$response->headers()->has('Content-Language') ||
            !$response->headers()->get('Content-Language') instanceof ContentLanguage
        ) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                $response
                    ->headers()
                    ->get('Content-Language')
                    ->values()
                    ->reduce(
                        new Set('string'),
                        function(Set $carry, ContentLanguageValue $language): Set {
                            return $carry->add((string) $language);
                        }
                    )
            )
        );
    }

    public static function key(): string
    {
        return 'languages';
    }
}
