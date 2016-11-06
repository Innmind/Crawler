<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute
};
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Http\{
    Message\RequestInterface,
    Message\ResponseInterface,
    Header\ContentLanguage,
    Header\ContentLanguageValue
};
use Innmind\Immutable\{
    MapInterface,
    Set
};

final class LanguagesParser implements ParserInterface
{
    private $clock;

    public function __construct(TimeContinuumInterface $clock)
    {
        $this->clock = $clock;
    }

    public function parse(
        RequestInterface $request,
        ResponseInterface $response,
        MapInterface $attributes
    ): MapInterface {
        $start = $this->clock->now();

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
                    ),
                $this
                    ->clock
                    ->now()
                    ->elapsedSince($start)
                    ->milliseconds()
            )
        );
    }

    public static function key(): string
    {
        return 'languages';
    }
}
