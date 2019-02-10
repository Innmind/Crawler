<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\Parser;
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\MapInterface;

/**
 * Iterate over all parsers until one modifies the attributes
 */
final class ConditionalParser implements Parser
{
    private $parsers;

    public function __construct(Parser ...$parsers)
    {
        $this->parsers = $parsers;
    }

    public function parse(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        $original = $attributes;

        foreach ($this->parsers as $parser) {
            $attributes = $parser->parse(
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
