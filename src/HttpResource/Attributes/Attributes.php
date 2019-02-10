<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource\Attributes;

use Innmind\Crawler\{
    HttpResource\Attributes as AttributesInterface,
    HttpResource\Attribute,
    Exception\DomainException,
};
use Innmind\Immutable\MapInterface;
use function Innmind\Immutable\assertMap;

final class Attributes implements AttributesInterface
{
    private $name;
    private $content;

    public function __construct(
        string $name,
        MapInterface $attributes
    ) {
        if (empty($name)) {
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

    public function content(): MapInterface
    {
        return $this->content;
    }

    public function current()
    {
        return $this->content->current();
    }

    public function key()
    {
        return $this->content->key();
    }

    public function next()
    {
        $this->content->next();
    }

    public function rewind()
    {
        $this->content->rewind();
    }

    public function valid()
    {
        return $this->content->valid();
    }
}
