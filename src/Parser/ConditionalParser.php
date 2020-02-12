<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\Parser;
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\Map;

/**
 * Iterate over all parsers until one modifies the attributes
 */
final class ConditionalParser implements Parser
{
    private array $parsers;

    public function __construct(Parser ...$parsers)
    {
        $this->parsers = $parsers;
    }

    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        $original = $attributes;

        foreach ($this->parsers as $parse) {
            $attributes = $parse(
                $request,
                $response,
                $attributes
            );

            if ($attributes !== $original) {
                break;
            }
        }

        return $attributes;
    }

    public static function key(): string
    {
        return 'conditional';
    }
}
