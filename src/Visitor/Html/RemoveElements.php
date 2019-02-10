<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Visitor\Html;

use Innmind\Xml\{
    Node,
    Element,
};
use Innmind\Immutable\Sequence;

final class RemoveElements
{
    private $toRemove;

    public function __construct(string ...$toRemove)
    {
        $this->toRemove = new Sequence(...$toRemove);
    }

    public function __invoke(Node $node): Node
    {
        $removedChildren = 0;

        return $node->children()->reduce(
            $node,
            function(Node $node, int $position, Node $child) use (&$removedChildren): Node {
                if (
                    $child instanceof Element &&
                    $this->toRemove->contains($child->name())
                ) {
                    return $node->removeChild(
                        $position - $removedChildren++
                    );
                }

                return $node->replaceChild(
                    $position - $removedChildren,
                    $this($child)
                );
            }
        );
    }
}
