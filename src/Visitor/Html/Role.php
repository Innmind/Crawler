<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Visitor\Html;

use Innmind\Crawler\Exception\DomainException;
use Innmind\Xml\{
    NodeInterface,
    ElementInterface
};
use Innmind\Immutable\{
    SetInterface,
    Set
};

/**
 * Find all elements that have the given role
 */
final class Role
{
    private $role;

    public function __construct(string $role)
    {
        if (empty($role)) {
            throw new DomainException;
        }

        $this->role = $role;
    }

    /**
     * @return SetInterface<ElementInterface>
     */
    public function __invoke(NodeInterface $node): SetInterface
    {
        $set = new Set(ElementInterface::class);

        if ($this->check($node)) {
            $set = $set->add($node);
        }

        return $node->children()->reduce(
            $set,
            function(Set $set, int $position, NodeInterface $node): Set {
                return $set->merge(
                    $this($node)
                );
            }
        );
    }

    private function check(NodeInterface $node): bool
    {
        return $node instanceof ElementInterface &&
            $node->attributes()->contains('role') &&
            $node->attributes()->get('role')->value() === $this->role;
    }
}
