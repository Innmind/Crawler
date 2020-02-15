<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource\Attributes;

use Innmind\Crawler\{
    HttpResource\Attributes as AttributesInterface,
    HttpResource\Attribute,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use function Innmind\Immutable\assertMap;

final class Attributes implements AttributesInterface
{
    private string $name;
    /** @var Map<string, Attribute> */
    private Map $content;

    /**
     * @param Map<string, Attribute> $attributes
     */
    public function __construct(string $name, Map $attributes)
    {
        if (Str::of($name)->empty()) {
            throw new DomainException;
        }

        assertMap('string', Attribute::class, $attributes, 2);

        $this->name = $name;
        $this->content = $attributes;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Map<string, Attribute>
     */
    public function content(): Map
    {
        return $this->content;
    }
}
