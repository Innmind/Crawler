<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\Map;

interface Parser
{
    /**
     * @param Map<string, AttributeInterface> $attributes
     *
     * @return Map<string, AttributeInterface>
     */
    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map;

    public static function key(): string;
}
