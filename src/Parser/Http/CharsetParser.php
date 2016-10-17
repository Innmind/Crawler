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
use Innmind\Immutable\MapInterface;

final class CharsetParser implements ParserInterface
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
            !($header = $response->headers()->get('Content-Type')) instanceof ContentType ||
            !$header->values()->current()->parameters()->contains('charset')
        ) {
            return $attributes;
        }

        return $attributes->put(
            'charset',
            new Attribute(
                'charset',
                $header
                    ->values()
                    ->current()
                    ->parameters()
                    ->get('charset')
                    ->value(),
                $this
                    ->clock
                    ->now()
                    ->elapsedSince($start)
                    ->milliseconds()
            )
        );
    }
}
