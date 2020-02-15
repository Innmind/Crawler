<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Crawler\HttpResource\Attribute;
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\Map;

interface Parser
{
    /**
     * @param Map<string, Attribute> $attributes
     *
     * @return Map<string, Attribute>
     */
    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map;

    public static function key(): string;
}
