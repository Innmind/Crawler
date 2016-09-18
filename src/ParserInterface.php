<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Http\Message\ResponseInterface;
use Innmind\Immutable\MapInterface;

interface ParserInterface
{
    /**
     * @param MapInterface<string, AttributeInterface> $attributes
     *
     * @return MapInterface<string, AttributeInterface>
     */
    public function parse(
        ResponseInterface $response,
        MapInterface $attributes
    ): MapInterface;
}
