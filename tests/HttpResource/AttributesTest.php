<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\HttpResource;

use Innmind\Crawler\HttpResource\{
    Attributes,
    AttributesInterface,
    Attribute,
    AttributeInterface
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class AttributesTest extends TestCase
{
    public function testInterface()
    {
        $attributes = new Attributes(
            'foo',
            $content = (new Map('string', AttributeInterface::class))
                ->put('bar', new Attribute('bar', 42))
                ->put('baz', new Attribute('baz', 'idk'))
        );

        $this->assertInstanceOf(AttributesInterface::class, $attributes);
        $this->assertSame('foo', $attributes->name());
        $this->assertSame($content, $attributes->content());
    }

    /**
     * @expectedException Innmind\Crawler\Exception\InvalidArgumentException
     */
    public function testThrowWhenEmptyName()
    {
        new Attributes(
            '',
            new Map('string', AttributeInterface::class)
        );
    }

    /**
     * @expectedException Innmind\Crawler\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidAttributeMap()
    {
        new Attributes(
            'foo',
            new Map('int', 'int')
        );
    }

    public function testIterator()
    {
        $attributes = new Attributes(
            'foo',
            $content = (new Map('string', AttributeInterface::class))
                ->put('bar', new Attribute('bar', 42))
                ->put('baz', new Attribute('baz', 'idk'))
        );

        $this->assertSame($content->get('bar'), $attributes->current());
        $this->assertSame('bar', $attributes->key());
        $this->assertTrue($attributes->valid());
        $this->assertNull($attributes->next());
        $this->assertSame($content->get('baz'), $attributes->current());
        $this->assertSame('baz', $attributes->key());
        $this->assertTrue($attributes->valid());
        $attributes->next();
        $this->assertFalse($attributes->valid());
        $this->assertNull($attributes->rewind());
        $this->assertSame('bar', $attributes->key());
    }
}
