<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\HttpResource;

use Innmind\Crawler\HttpResource\{
    Attribute,
    AttributeInterface
};

class AttributeTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $attribute = new Attribute('foo', 42);

        $this->assertInstanceOf(AttributeInterface::class, $attribute);
        $this->assertSame('foo', $attribute->name());
        $this->assertSame(42, $attribute->content());
    }

    /**
     * @expectedException Innmind\Crawler\Exception\InvalidArgumentException
     */
    public function testThrowWhenEmptyName()
    {
        new Attribute('', 42);
    }
}
