<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\HttpResource;

use Innmind\Crawler\HttpResource\{
    Alternate,
    AttributeInterface
};
use Innmind\Url\{
    UrlInterface,
    Url
};
use Innmind\Immutable\{
    SetInterface,
    Set
};

class AlternateTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            AttributeInterface::class,
            $alternate = new Alternate(
                'fr',
                new Set(UrlInterface::class),
                42
            )
        );
        $this->assertSame('fr', $alternate->name());
        $this->assertInstanceOf(SetInterface::class, $alternate->content());
        $this->assertSame(
            UrlInterface::class,
            (string) $alternate->content()->type()
        );
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
        (new Alternate('fr', new Set(UrlInterface::class), 42))->merge(
            new Alternate('en', new Set(UrlInterface::class), 42)
        );
    }

    public function testMerge()
    {
        $first = new Alternate(
            'fr',
            (new Set(UrlInterface::class))->add($foo = Url::fromString('/foo')),
            42
        );
        $second = new Alternate(
            'fr',
            (new Set(UrlInterface::class))->add($bar = Url::fromString('/bar')),
            42
        );

        $third = $first->merge($second);

        $this->assertInstanceOf(Alternate::class, $third);
        $this->assertNotSame($first, $third);
        $this->assertNotSame($second, $third);
        $this->assertSame('fr', $third->name());
        $this->assertSame([$foo, $bar], $third->content()->toPrimitive());
        $this->assertSame(84, $third->parsingTime());
    }
}
