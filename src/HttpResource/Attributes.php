<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource;

use Innmind\Crawler\Exception\InvalidArgumentException;
use Innmind\Immutable\MapInterface;

final class Attributes implements AttributesInterface
{
    private $name;
    private $content;
    private $parsingTime;

    public function __construct(
        string $name,
        MapInterface $attributes
    ) {
        if (
            empty($name) ||
            (string) $attributes->keyType() !== 'string' ||
            (string) $attributes->valueType() !== AttributeInterface::class
        ) {
            throw new InvalidArgumentException;
        }

        $this->name = $name;
        $this->content = $attributes;
        $this->parsingTime = $attributes->reduce(
            0,
            function (int $parsingTime, string $name, AttributeInterface $attribute): int {
                return $parsingTime += $attribute->parsingTime();
            }
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function content(): MapInterface
    {
        return $this->content;
    }

    public function parsingTime(): int
    {
        return $this->parsingTime;
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
