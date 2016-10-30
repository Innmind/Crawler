<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\HttpResource;

use Innmind\Crawler\HttpResource\{
    Alternate,
    AttributeInterface
};
use Innmind\Immutable\Set;

class AlternateTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            AttributeInterface::class,
            $alternate = new Alternate(
                'fr',
                $links = new Set('string'),
                42
            )
        );
        $this->assertSame('fr', $alternate->name());
        $this->assertSame($links, $alternate->content());
        $this->assertSame(42, $alternate->parsingTime());
    }

    /**
     * @expectedException Innmind\Crawler\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidLinks()
    {
        new Alternate('fr', new Set('int'), 42);
    }

    /**
     * @expectedException Innmind\Crawler\Exception\CantMergeDifferentLanguagesException
     */
    public function testThrowWhenMergingDifferentLanguages()
    {
        (new Alternate('fr', new Set('string'), 42))->merge(
            new Alternate('en', new Set('string'), 42)
        );
    }

    public function testMerge()
    {
        $first = new Alternate(
            'fr',
            (new Set('string'))->add('foo'),
            42
        );
        $second = new Alternate(
            'fr',
            (new Set('string'))->add('bar'),
            42
        );

        $third = $first->merge($second);

        $this->assertInstanceOf(Alternate::class, $third);
        $this->assertNotSame($first, $third);
        $this->assertNotSame($second, $third);
        $this->assertSame('fr', $third->name());
        $this->assertSame(['foo', 'bar'], $third->content()->toPrimitive());
        $this->assertSame(84, $third->parsingTime());
    }
}
