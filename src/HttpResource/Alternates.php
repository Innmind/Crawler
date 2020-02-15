<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource;

use Innmind\Crawler\Exception\InvalidArgumentException;
use Innmind\Immutable\Map;

final class Alternates implements Attributes
{
    private Attributes $attributes;

    /**
     * @param Map<string, Attribute> $alternates
     */
    public function __construct(Map $alternates)
    {
        $this->attributes = new Attributes\Attributes(
            'alternates',
            $alternates,
        );

        $alternates->foreach(function(string $language, Attribute $alternate) {
            if (!$alternate instanceof Alternate) {
                throw new InvalidArgumentException;
            }
        });
    }

    public function name(): string
    {
        return $this->attributes->name();
    }

    /**
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function content(): Map
    {
        return $this->attributes->content();
    }

    public function merge(self $alternates): self
    {
        /** @var Map<string, Attribute> */
        $languages = $this
            ->content()
            ->keys()
            ->merge($alternates->content()->keys())
            ->toMapOf(
                'string',
                Attribute::class,
                function(string $language) use ($alternates): \Generator {
                    if (!$this->content()->contains($language)) {
                        yield $language => $alternates->content()->get($language);

                        return;
                    }

                    if (!$alternates->content()->contains($language)) {
                        yield $language => $this->content()->get($language);

                        return;
                    }

                    /** @psalm-suppress MixedMethodCall */
                    yield $language => $this
                        ->content()
                        ->get($language)
                        ->merge(
                            $alternates
                                ->content()
                                ->get($language)
                        );
                },
            );

        return new self($languages);
    }
}
