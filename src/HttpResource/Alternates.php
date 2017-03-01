<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource;

use Innmind\Crawler\Exception\InvalidArgumentException;
use Innmind\Immutable\{
    Map,
    MapInterface
};

final class Alternates implements AttributesInterface
{
    private $attributes;

    public function __construct(MapInterface $alternates)
    {
        $this->attributes = new Attributes(
            'alternates',
            $alternates
        );

        $alternates->foreach(function(string $language, AttributeInterface $alternate) {
            if (!$alternate instanceof Alternate) {
                throw new InvalidArgumentException;
            }
        });
    }

    public function name(): string
    {
        return $this->attributes->name();
    }

    public function content(): MapInterface
    {
        return $this->attributes->content();
    }

    public function parsingTime(): int
    {
        return $this->attributes->parsingTime();
    }

    public function current()
    {
        return $this->attributes->current();
    }

    public function key()
    {
        return $this->attributes->key();
    }

    public function next()
    {
        $this->attributes->next();
    }

    public function rewind()
    {
        $this->attributes->rewind();
    }

    public function valid()
    {
        return $this->attributes->valid();
    }

    public function merge(self $alternates): self
    {
        $languages = $this
            ->content()
            ->keys()
            ->merge($alternates->content()->keys())
            ->reduce(
                new Map('string', AttributeInterface::class),
                function(Map $all, string $language) use ($alternates): Map {
                    if (!$this->content()->contains($language)) {
                        return $all->put(
                            $language,
                            $alternates->content()->get($language)
                        );
                    }

                    if (!$alternates->content()->contains($language)) {
                        return $all->put(
                            $language,
                            $this->content()->get($language)
                        );
                    }

                    return $all->put(
                        $language,
                        $this
                            ->content()
                            ->get($language)
                            ->merge(
                                $alternates
                                    ->content()
                                    ->get($language)
                            )
                    );
                }
            );

        return new self($languages);
    }
}
