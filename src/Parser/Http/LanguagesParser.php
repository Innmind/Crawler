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
    Map,
    Set,
};

final class LanguagesParser implements Parser
{
    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        if (
            !$response->headers()->contains('Content-Language') ||
            !$response->headers()->get('Content-Language') instanceof ContentLanguage
        ) {
            return $attributes;
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        return ($attributes)(
            self::key(),
            new Attribute(
                self::key(),
                $response
                    ->headers()
                    ->get('Content-Language')
                    ->values()
                    ->mapTo(
                        'string',
                        static fn(ContentLanguageValue $language): string => $language->toString(),
                    ),
            ),
        );
    }

    public static function key(): string
    {
        return 'languages';
    }
}
