<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\HttpResource\Attributes;

use Innmind\Crawler\{
    HttpResource\Attributes\Attributes,
    HttpResource\Attributes as AttributesInterface,
    HttpResource\Attribute,
    Exception\DomainException,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class AttributesTest extends TestCase
{
    public function testInterface()
    {
        $attributes = new Attributes(
            'foo',
            $content = Map::of('string', Attribute::class)
                ('bar', new Attribute\Attribute('bar', 42))
                ('baz', new Attribute\Attribute('baz', 'idk'))
        );

        $this->assertInstanceOf(AttributesInterface::class, $attributes);
        $this->assertSame('foo', $attributes->name());
        $this->assertSame($content, $attributes->content());
    }

    public function testThrowWhenEmptyName()
    {
        $this->expectException(DomainException::class);

        new Attributes(
            '',
            new Map('string', Attribute::class)
        );
    }

    public function testThrowWhenInvalidAttributeMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type MapInterface<string, Innmind\Crawler\HttpResource\Attribute>');

        new Attributes(
            'foo',
            new Map('int', 'int')
        );
    }

    public function testIterator()
    {
        $attributes = new Attributes(
            'foo',
            $content = Map::of('string', Attribute::class)
                ('bar', new Attribute\Attribute('bar', 42))
                ('baz', new Attribute\Attribute('baz', 'idk'))
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
