<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Innmind\Immutable\MapInterface;

interface ParserInterface
{
    /**
     * @param MapInterface<string, AttributeInterface> $attributes
     *
     * @return MapInterface<string, AttributeInterface>
     */
    public function parse(
        RequestInterface $request,
        ResponseInterface $response,
        MapInterface $attributes
    ): MapInterface;

    public static function key(): string;
}
