<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Http\Message\{
    Request,
    Response
};
use Innmind\Immutable\MapInterface;

final class SequenceParser implements ParserInterface
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
