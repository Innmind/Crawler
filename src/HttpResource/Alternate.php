<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource;

use Innmind\Crawler\{
    Visitor\RemoveDuplicatedUrls,
    Exception\InvalidArgumentException,
    Exception\CantMergeDifferentLanguagesException
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\SetInterface;

final class Alternate implements Attribute
{
    private $attribute;

    public function __construct(
        string $language,
        SetInterface $links
    ) {
        if ((string) $links->type() !== UrlInterface::class) {
            throw new InvalidArgumentException;
        }

        $this->attribute = new Attribute\Attribute(
            $language,
            (new RemoveDuplicatedUrls)($links)
        );
    }

    public function name(): string
    {
        return $this->attribute->name();
    }

    public function content()
    {
        return $this->attribute->content();
    }

    public function merge(self $alternate): self
    {
        if ($this->name() !== $alternate->name()) {
            throw new CantMergeDifferentLanguagesException;
        }

        return new self(
            $this->name(),
            $this->content()->merge(
                $alternate->content()
            )
        );
    }
}
