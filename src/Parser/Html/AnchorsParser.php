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
    Visitor\Element,
    Exception\ElementNotFound,
    Element\A,
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

final class AnchorsParser implements Parser
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
            $anchors = (new Elements('a'))(
                Element::body()($document)
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        /**
         * @psalm-suppress ArgumentTypeCoercion
         */
        $anchors = $anchors
            ->filter(static function(Node $node): bool {
                return $node instanceof A;
            })
            ->filter(static function(A $anchor): bool {
                return Str::of($anchor->href()->toString())->matches('~^#~');
            })
            ->reduce(
                Set::strings(),
                static function(Set $anchors, A $anchor): Set {
                    return $anchors->add(
                        Str::of($anchor->href()->toString())->substring(1)->toString(),
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
