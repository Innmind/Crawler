<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\HttpResource\Attribute;

use Innmind\Crawler\HttpResource\{
    Attribute\Attribute,
    Attribute as AttributeInterface,
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

    /**
     * @expectedException Innmind\Crawler\Exception\DomainException
     */
    public function testThrowWhenEmptyName()
    {
        new Attribute('', 42);
    }
}
