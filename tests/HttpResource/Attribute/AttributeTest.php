<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\HttpResource\Attribute;

use Innmind\Crawler\{
    HttpResource\Attribute\Attribute,
    HttpResource\Attribute as AttributeInterface,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class AttributeTest extends TestCase
{
    public function testInterface()
    {
        $attribute = new Attribute('foo', 42);

        $this->assertInstanceOf(AttributeInterface::class, $attribute);
        $this->assertSame('foo', $attribute->name());
        $this->assertSame(42, $attribute->content());
    }

    public function testThrowWhenEmptyName()
    {
        $this->expectException(DomainException::class);

        new Attribute('', 42);
    }
}
