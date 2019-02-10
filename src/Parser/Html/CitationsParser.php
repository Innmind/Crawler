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
    Visitor\Body,
    Exception\ElementNotFound,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Set,
    Str,
};

final class CitationsParser implements Parser
{
    use HtmlTrait;

    private $read;

    public function __construct(Reader $read)
    {
        $this->read = $read;
    }

    public function parse(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        if (!$this->isHtml($attributes)) {
            return $attributes;
        }

        $document = ($this->read)($response->body());

        try {
            $citations = (new Elements('cite'))(
                (new Body)($document)
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        $citations = $citations->reduce(
            new Set('string'),
            function(SetInterface $citations, Node $cite): SetInterface {
                return $citations->add(
                    (string) Str::of((new Text)($cite))->trim()
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
