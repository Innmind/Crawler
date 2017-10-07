<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Http\Message\{
    Request,
    Response
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
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface;

    public static function key(): string;
}
