<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\HttpResource;

use Innmind\Crawler\{
    HttpResource\Alternate,
    HttpResource\Attribute,
    Exception\CantMergeDifferentLanguages,
};
use Innmind\Url\Url;
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class AlternateTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Attribute::class,
            $alternate = new Alternate(
                'fr',
                Set::of(Url::class)
            )
        );
        $this->assertSame('fr', $alternate->name());
        $this->assertInstanceOf(Set::class, $alternate->content());
        $this->assertSame(
            Url::class,
            (string) $alternate->content()->type()
        );
    }

    public function testThrowWhenInvalidLinks()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Set<Innmind\Url\Url>');

        new Alternate('fr', Set::of('int'));
    }

    public function testThrowWhenMergingDifferentLanguages()
    {
        $this->expectException(CantMergeDifferentLanguages::class);

        (new Alternate('fr', Set::of(Url::class)))->merge(
            new Alternate('en', Set::of(Url::class))
        );
    }

    public function testMerge()
    {
        $first = new Alternate(
            'fr',
            Set::of(Url::class, $foo = Url::of('/foo'))
        );
        $second = new Alternate(
            'fr',
            Set::of(Url::class, $bar = Url::of('/bar'))
        );

        $third = $first->merge($second);

        $this->assertInstanceOf(Alternate::class, $third);
        $this->assertNotSame($first, $third);
        $this->assertNotSame($second, $third);
        $this->assertSame('fr', $third->name());
        $this->assertSame([$foo, $bar], unwrap($third->content()));
    }
}
