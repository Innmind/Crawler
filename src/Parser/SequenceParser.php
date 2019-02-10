<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\Parser;
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\MapInterface;

final class SequenceParser implements Parser
{
    private $parsers;
    private $length;

    public function __construct(Parser ...$parsers)
    {
        $this->parsers = $parsers;
        $this->length = count($parsers);
    }

    public function parse(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        for ($i = 0; $i < $this->length; $i++) {
            $attributes = $this->parsers[$i]->parse(
                $request,
                $response,
                $attributes
            );
        }

        return $attributes;
    }

    public static function key(): string
    {
        return 'sequence';
    }
}
