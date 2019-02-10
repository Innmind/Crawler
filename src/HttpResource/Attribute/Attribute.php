<?php
declare(strict_types = 1);

namespace Innmind\Crawler\HttpResource\Attribute;

use Innmind\Crawler\{
    HttpResource\Attribute as AttributeInterface,
    Exception\DomainException,
};

final class Attribute implements AttributeInterface
{
    private $name;
    private $content;

    public function __construct(string $name, $content)
    {
        if (empty($name)) {
            throw new DomainException;
        }

        $this->name = $name;
        $this->content = $content;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function content()
    {
        return $this->content;
    }
}
