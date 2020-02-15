<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Visitor\Html;

use Innmind\Xml\{
    Node,
    Node\Comment,
};
use Innmind\Immutable\Map;

final class RemoveComments
{
    public function __invoke(Node $node): Node
    {
        $removedChildren = 0;

        $children = $node->children()->reduce(
            Map::of('int', Node::class),
            static function(Map $children, Node $child): Map {
                return ($children)($children->size(), $child);
            },
        );

        return $children->reduce(
            $node,
            function(Node $node, int $position, Node $child) use (&$removedChildren): Node {
                if ($child instanceof Comment) {
                    /**
                     * @psalm-suppress MixedArgument
                     * @psalm-suppress MixedOperand
                     */
                    return $node->removeChild(
                        $position - $removedChildren++
                    );
                }

                /**
                 * @psalm-suppress MixedArgument
                 * @psalm-suppress MixedOperand
                 */
                return $node->replaceChild(
                    $position - $removedChildren,
                    $this($child)
                );
            }
        );
    }
}
