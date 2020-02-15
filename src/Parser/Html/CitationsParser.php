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
                Element::body()($document),
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        $citations = $citations->mapTo(
            'string',
            static fn(Node $cite): string => Str::of((new Text)($cite))->trim()->toString(),
        );

        if ($citations->empty()) {
            return $attributes;
        }

        return ($attributes)(
            self::key(),
            new Attribute(self::key(), $citations),
        );
    }

    public static function key(): string
    {
        return 'citations';
    }
}
