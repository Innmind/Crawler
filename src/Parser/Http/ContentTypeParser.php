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
    Header\ContentType
};
use Innmind\Filesystem\MediaType\MediaType;
use Innmind\Immutable\MapInterface;

final class ContentTypeParser implements ParserInterface
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
        return 'content_type';
    }
}
