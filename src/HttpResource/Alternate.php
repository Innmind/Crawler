<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource;

use Innmind\Crawler\{
    Visitor\RemoveDuplicatedUrls,
    Exception\CantMergeDifferentLanguages,
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
            throw new \TypeError(sprintf(
                'Argument 2 must be of type SetInterface<%s>',
                UrlInterface::class
            ));
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
            throw new CantMergeDifferentLanguages;
        }

        return new self(
            $this->name(),
            $this->content()->merge(
                $alternate->content()
            )
        );
    }
}
