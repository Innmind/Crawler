<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Visitor\Html;

use Innmind\Xml\{
    Element,
    Node,
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Element as Search,
    Exception\ElementNotFound,
};
use Innmind\Immutable\Set;

final class OpenGraph
{
    private string $property;
    private Search $head;
    private Elements $metas;

    public function __construct(string $property)
    {
        $this->property = 'og:'.$property;
        $this->head = Search::head();
        $this->metas = new Elements('meta');
    }

    /**
     * @return Set<string> Values of the properties
     */
    public function __invoke(Node $node): Set
    {
        try {
            /** @var Set<string> */
            return ($this->metas)(
                ($this->head)($node),
            )
                ->filter(static function(Element $meta): bool {
                    return $meta->attributes()->contains('property') &&
                        $meta->attributes()->contains('content');
                })
                ->filter(function(Element $meta): bool {
                    return $meta->attributes()->get('property')->value() === $this->property;
                })
                ->mapTo(
                    'string',
                    static fn(Element $meta): string => $meta->attributes()->get('content')->value(),
                );
        } catch (ElementNotFound $e) {
            return Set::strings();
        }
    }
}
