<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource;

use Innmind\Crawler\Exception\InvalidArgumentException;

final class Attribute implements AttributeInterface
{
    private $name;
    private $content;
    private $parsingTime;

    public function __construct(string $name, $content, int $parsingTime)
    {
        if (empty($name) || $parsingTime < 0) {
            throw new InvalidArgumentException;
        }

        $this->name = $name;
        $this->content = $content;
        $this->parsingTime = $parsingTime;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function content()
    {
        return $this->content;
    }

    public function parsingTime(): int
    {
        return $this->parsingTime;
    }
}
