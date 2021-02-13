<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource;

use Innmind\Immutable\Map;

final class Alternates implements Attributes
{
    /** @var Map<string, Alternate> */
    private Map $alternates;

    /**
     * @param Map<string, Attribute> $alternates
     */
    public function __construct(Map $alternates)
    {
        /** @var Map<string, Alternate> */
        $this->alternates = $alternates->toMapOf( // simply a type change
            'string',
            Alternate::class,
            static function(string $language, Attribute $alternate): \Generator {
                yield $language => $alternate;
            },
        );
    }

    public function name(): string
    {
        return 'alternates';
    }

    /**
     * @psalm-suppress ImplementedReturnTypeMismatch Alternate is an Attribute
     * @return Map<string, Alternate>
     */
    public function content(): Map
    {
        return $this->alternates;
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
