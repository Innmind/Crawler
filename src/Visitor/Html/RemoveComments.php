<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Visitor\Html;

use Innmind\Xml\{
    Node,
    Node\Comment,
};

final class RemoveComments
{
    public function __invoke(Node $node): Node
    {
        $removedChildren = 0;

        return $node
            ->children()
            ->reduce(
                $node,
                function(
                    Node $node,
                    int $position,
                    Node $child
                ) use (
                    &$removedChildren
                ): Node {
                    if ($child instanceof Comment) {
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
