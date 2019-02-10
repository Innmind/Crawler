<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
    Visitor\Html\OpenGraph,
};
use Innmind\Xml\Reader;
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\MapInterface;

final class TitleParser implements Parser
{
    private $read;
    private $extract;

    public function __construct(Reader $read)
    {
        $this->read = $read;
        $this->extract = new OpenGraph(self::key());
    }

    public function __invoke(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        $document = ($this->read)($response->body());

        $values = ($this->extract)($document);

        if ($values->empty()) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), $values->current())
        );
    }

    public static function key(): string
    {
        return 'title';
    }
}
