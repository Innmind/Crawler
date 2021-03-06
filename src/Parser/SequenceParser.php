<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\Parser;
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\Map;

final class SequenceParser implements Parser
{
    /** @var list<Parser> */
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
        foreach ($this->parsers as $parse) {
            $attributes = $parse(
                $request,
                $response,
                $attributes,
            );
        }

        return $attributes;
    }

    public static function key(): string
    {
        return 'sequence';
    }
}
