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
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Body,
    Exception\ElementNotFound,
    Element\A,
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

final class AnchorsParser implements Parser
{
    private $read;

    public function __construct(Reader $read)
    {
        $this->read = $read;
    }

    public function __invoke(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        $document = ($this->read)($response->body());

        try {
            $anchors = (new Elements('a'))(
                (new Body)($document)
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        $anchors = $anchors
            ->filter(static function(Node $node): bool {
                return $node instanceof A;
            })
            ->filter(static function(A $anchor): bool {
                return Str::of((string) $anchor->href())->matches('~^#~');
            })
            ->reduce(
                new Set('string'),
                static function(SetInterface $anchors, A $anchor): SetInterface {
                    return $anchors->add(
                        (string) Str::of((string) $anchor->href())->substring(1)
                    );
                }
            );

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), $anchors)
        );
    }

    public static function key(): string
    {
        return 'anchors';
    }
}
