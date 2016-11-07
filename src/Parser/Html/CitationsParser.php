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
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Innmind\Immutable\{
    MapInterface,
    Set
};

final class CitationsParser implements ParserInterface
{
    use HtmlTrait;

    private $reader;
    private $clock;

    public function __construct(
        ReaderInterface $reader,
        TimeContinuumInterface $clock
    ) {
        $this->reader = $reader;
        $this->clock = $clock;
    }

    public function parse(
        RequestInterface $request,
        ResponseInterface $response,
        MapInterface $attributes
    ): MapInterface {
        $start = $this->clock->now();

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
            new Attribute(
                self::key(),
                $citations,
                $this
                    ->clock
                    ->now()
                    ->elapsedSince($start)
                    ->milliseconds()
            )
        );
    }

    public static function key(): string
    {
        return 'citations';
    }
}
