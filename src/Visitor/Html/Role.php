<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Visitor\Html;

use Innmind\Crawler\Exception\DomainException;
use Innmind\Xml\{
    Node,
    Element,
};
use Innmind\Immutable\{
    Set,
    Str,
};

/**
 * Find all elements that have the given role
 */
final class Role
{
    private string $role;

    public function __construct(string $role)
    {
        if (Str::of($role)->empty()) {
            throw new DomainException;
        }

        $this->role = $role;
    }

    /**
     * @return Set<Element>
     */
    public function __invoke(Node $node): Set
    {
        /** @var Set<Element> */
        $set = Set::of(Element::class);

        if ($this->check($node)) {
            /** @psalm-suppress ArgumentTypeCoercion */
            $set = ($set)($node);
        }

        /** @var Set<Element> */
        return $node->children()->reduce(
            $set,
            function(Set $set, Node $node): Set {
                return $set->merge(
                    $this($node),
                );
            }
        );
    }

    private function check(Node $node): bool
    {
        return $node instanceof Element &&
            $node->attributes()->contains('role') &&
            $node->attributes()->get('role')->value() === $this->role;
    }
}
