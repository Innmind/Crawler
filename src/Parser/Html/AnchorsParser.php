<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute
};
use Innmind\Xml\{
    ReaderInterface,
    NodeInterface
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Body,
    Exception\ElementNotFoundException,
    Element\A
};
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Innmind\Immutable\{
    MapInterface,
    Set,
    Str
};

final class AnchorsParser implements ParserInterface
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
            $anchors = (new Elements('a'))(
                (new Body)($document)
            );
        } catch (ElementNotFoundException $e) {
            return $attributes;
        }

        $anchors = $anchors
            ->filter(function(NodeInterface $node): bool {
                return $node instanceof A;
            })
            ->filter(function(A $anchor): bool {
                return (new Str((string) $anchor->href()))->matches('~^#~');
            })
            ->reduce(
                new Set('string'),
                function(Set $anchors, A $anchor): Set {
                    return $anchors->add(
                        substr((string) $anchor->href(), 1)
                    );
                }
            );

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                $anchors,
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
        return 'anchors';
    }
}
