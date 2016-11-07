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
        $attribute = new Attribute('foo', 42, 24);

        $this->assertInstanceOf(AttributeInterface::class, $attribute);
        $this->assertSame('foo', $attribute->name());
        $this->assertSame(42, $attribute->content());
        $this->assertSame(24, $attribute->parsingTime());
    }

    /**
     * @expectedException Innmind\Crawler\Exception\InvalidArgumentException
     */
    public function testThrowWhenEmptyName()
    {
        new Attribute('', 42, 24);
    }

    /**
     * @expectedException Innmind\Crawler\Exception\InvalidArgumentException
     */
    public function testThrowWhenParsingTimeIsNegative()
    {
        new Attribute('foo', 42, -24);
    }
}
