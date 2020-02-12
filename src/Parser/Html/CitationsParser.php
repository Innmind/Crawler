<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
};
use Innmind\Xml\{
    Reader,
    Node,
    Visitor\Text,
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Element,
    Exception\ElementNotFound,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\{
    Map,
    Set,
    Str,
};

final class CitationsParser implements Parser
{
    private Reader $read;

    public function __construct(Reader $read)
    {
        $this->read = $read;
    }

    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        $document = ($this->read)($response->body());

        try {
            $citations = (new Elements('cite'))(
                Element::body()($document)
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        $citations = $citations->reduce(
            Set::of('string'),
            function(Set $citations, Node $cite): Set {
                return $citations->add(
                    Str::of((new Text)($cite))->trim()->toString(),
                );
            }
        );

        if ($citations->size() === 0) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), $citations)
        );
    }

    public static function key(): string
    {
        return 'citations';
    }
}
