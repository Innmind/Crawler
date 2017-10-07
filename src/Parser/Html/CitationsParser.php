<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute
};
use Innmind\Xml\{
    ReaderInterface,
    NodeInterface,
    Visitor\Text
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Body,
    Exception\ElementNotFoundException
};
use Innmind\Http\Message\{
    Request,
    Response
};
use Innmind\Immutable\{
    MapInterface,
    Set
};

final class CitationsParser implements ParserInterface
{
    use HtmlTrait;

    private $reader;

    public function __construct(ReaderInterface $reader)
    {
        $this->reader = $reader;
    }

    public function parse(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        if (!$this->isHtml($attributes)) {
            return $attributes;
        }

        $document = $this->reader->read($response->body());

        try {
            $citations = (new Elements('cite'))(
                (new Body)($document)
            );
        } catch (ElementNotFoundException $e) {
            return $attributes;
        }

        $citations = $citations->reduce(
            new Set('string'),
            function(Set $citations, NodeInterface $cite): Set {
                return $citations->add(
                    trim((new Text)($cite))
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
