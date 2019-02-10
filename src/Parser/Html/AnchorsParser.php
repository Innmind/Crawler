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
            $anchors = (new Elements('a'))(
                (new Body)($document)
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        $anchors = $anchors
            ->filter(function(Node $node): bool {
                return $node instanceof A;
            })
            ->filter(function(A $anchor): bool {
                return (new Str((string) $anchor->href()))->matches('~^#~');
            })
            ->reduce(
                new Set('string'),
                function(SetInterface $anchors, A $anchor): SetInterface {
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
