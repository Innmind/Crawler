<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource;

use Innmind\Crawler\{
    Visitor\RemoveDuplicatedUrls,
    Exception\CantMergeDifferentLanguages,
};
use Innmind\Url\Url;
use Innmind\Immutable\Set;
use function Innmind\Immutable\assertSet;

final class Alternate implements Attribute
{
    private Attribute $attribute;

    /**
     * @param Set<Url> $links
     */
    public function __construct(string $language, Set $links)
    {
        assertSet(Url::class, $links, 2);

        $this->attribute = new Attribute\Attribute(
            $language,
            (new RemoveDuplicatedUrls)($links),
        );
    }

    public function name(): string
    {
        return $this->attribute->name();
    }

    /**
     * @return Set<Url>
     */
    public function content()
    {
        /** @var Set<Url> */
        return $this->attribute->content();
    }

    public function merge(self $alternate): self
    {
        if ($this->name() !== $alternate->name()) {
            throw new CantMergeDifferentLanguages;
        }

        return new self(
            $this->name(),
            $this->content()->merge(
                $alternate->content(),
            ),
        );
    }
}
