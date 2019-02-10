<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\HttpResource;

use Innmind\Crawler\HttpResource\{
    Alternate,
    Attribute,
};
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
};
use PHPUnit\Framework\TestCase;

class AlternateTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Attribute::class,
            $alternate = new Alternate(
                'fr',
                new Set(UrlInterface::class)
            )
        );
        $this->assertSame('fr', $alternate->name());
        $this->assertInstanceOf(SetInterface::class, $alternate->content());
        $this->assertSame(
            UrlInterface::class,
            (string) $alternate->content()->type()
        );
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessae Argument 2 must be of type SetInterface<Innmind\Url\UrlInterface>
     */
    public function testThrowWhenInvalidLinks()
    {
        new Alternate('fr', new Set('int'));
    }

    /**
     * @expectedException Innmind\Crawler\Exception\CantMergeDifferentLanguages
     */
    public function testThrowWhenMergingDifferentLanguages()
    {
        (new Alternate('fr', new Set(UrlInterface::class)))->merge(
            new Alternate('en', new Set(UrlInterface::class))
        );
    }

    public function testMerge()
    {
        $first = new Alternate(
            'fr',
            Set::of(UrlInterface::class, $foo = Url::fromString('/foo'))
        );
        $second = new Alternate(
            'fr',
            Set::of(UrlInterface::class, $bar = Url::fromString('/bar'))
        );

        $third = $first->merge($second);

        $this->assertInstanceOf(Alternate::class, $third);
        $this->assertNotSame($first, $third);
        $this->assertNotSame($second, $third);
        $this->assertSame('fr', $third->name());
        $this->assertSame([$foo, $bar], $third->content()->toPrimitive());
    }
}
