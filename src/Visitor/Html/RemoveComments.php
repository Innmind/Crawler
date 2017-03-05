<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Visitor\Html;

use Innmind\Xml\{
    NodeInterface,
    Node\Comment
};

final class RemoveComments
{
    public function __invoke(NodeInterface $node): NodeInterface
    {
        $removedChildren = 0;

        return $node
            ->children()
            ->reduce(
                $node,
                function(
                    NodeInterface $node,
                    int $position,
                    NodeInterface $child
                ) use (
                    &$removedChildren
                ): NodeInterface {
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
