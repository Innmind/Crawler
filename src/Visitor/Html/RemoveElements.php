<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Visitor\Html;

use Innmind\Xml\{
    NodeInterface,
    ElementInterface
};
use Innmind\Immutable\SetInterface;

final class RemoveElements
{
    private $toRemove;

    public function __construct(SetInterface $toRemove)
    {
        if ((string) $toRemove->type() !== 'string') {
            throw new \TypeError('Argument 1 must be of type SetInterface<string>');
        }

        $this->toRemove = $toRemove;
    }

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
                    if (
                        $child instanceof ElementInterface &&
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
