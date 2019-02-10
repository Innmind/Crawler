<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Visitor\Html;

use Innmind\Xml\{
    Element,
    Node,
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Head,
    Exception\ElementNotFound,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class OpenGraph
{
    private $property;
    private $head;
    private $metas;

    public function __construct(string $property)
    {
        $this->property = 'og:'.$property;
        $this->head = new Head;
        $this->metas = new Elements('meta');
    }

    /**
     * @return SetInterface<string> Values of the properties
     */
    public function __invoke(Node $node): SetInterface
    {
        try {
            return ($this->metas)(
                ($this->head)($node)
            )
                ->filter(static function(Element $meta): bool {
                    return $meta->attributes()->contains('property') &&
                        $meta->attributes()->contains('content');
                })
                ->filter(function(Element $meta): bool {
                    return $meta->attributes()->get('property')->value() === $this->property;
                })
                ->reduce(
                    Set::of('string'),
                    static function(SetInterface $values, Element $meta): SetInterface {
                        return $values->add(
                            $meta->attributes()->get('content')->value()
                        );
                    }
                );
        } catch (ElementNotFound $e) {
            return Set::of('string');
        }
    }
}
