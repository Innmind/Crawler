<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource;

use Innmind\Crawler\Exception\{
    InvalidArgumentException,
    CantMergeDifferentLanguagesException
};
use Innmind\Immutable\SetInterface;

final class Alternate implements AttributeInterface
{
    private $attribute;

    public function __construct(
        string $language,
        SetInterface $links,
        int $parsingTime
    ) {
        if ((string) $links->type() !== 'string') {
            throw new InvalidArgumentException;
        }

        $this->attribute = new Attribute($language, $links, $parsingTime);
    }

    public function name(): string
    {
        return $this->attribute->name();
    }

    public function content()
    {
        return $this->attribute->content();
    }

    public function parsingTime(): int
    {
        return $this->attribute->parsingTime();
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
            ),
            $this->parsingTime() + $alternate->parsingTime()
        );
    }
}
