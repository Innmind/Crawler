<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Http\Message\{
    Request,
    Response
};
use Innmind\Immutable\MapInterface;

/**
 * Iterate over all parsers until one modifies the attributes
 */
final class ConditionalParser implements ParserInterface
{
    private $parsers;
    private $length;

    public function __construct(ParserInterface ...$parsers)
    {
        $this->parsers = $parsers;
        $this->length = count($parsers);
    }

    public function parse(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        $original = $attributes;

        for ($i = 0; $i < $this->length; $i++) {
            $attributes = $this->parsers[$i]->parse(
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
