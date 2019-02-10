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
    Header\ContentLanguage,
    Header\ContentLanguageValue,
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Set,
};

final class LanguagesParser implements Parser
{
    public function __invoke(
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
                        static function(SetInterface $carry, ContentLanguageValue $language): SetInterface {
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
