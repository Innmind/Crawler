<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\HttpResource;

use Innmind\Crawler\{
    HttpResource\Alternates,
    HttpResource\Alternate,
    HttpResource\Attribute,
    HttpResource\Attributes,
    Exception\InvalidArgumentException,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class AlternatesTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Attributes::class,
            $alternates = new Alternates(
                Map::of('string', Attribute::class)
            )
        );
        $this->assertSame('alternates', $alternates->name());
        $this->assertInstanceOf(Map::class, $alternates->content());
        $this->assertSame('string', $alternates->content()->keyType());
        $this->assertSame(Alternate::class, $alternates->content()->valueType());
    }

    public function testThrowWhenNotOnlyAlternates()
    {
        $this->expectException(\TypeError::class);

        new Alternates(
            Map::of('string', Attribute::class)
                (
                    'fr',
                    $this->createMock(Attribute::class)
                )
        );
    }

    public function testMerge()
    {
        $first = new Alternates(
            Map::of('string', Attribute::class)
                (
                    'de',
                    new Alternate(
                        'de',
                        Set::of(Url::class, $de = Url::of('/de'))
                    )
                )
                (
                    'en',
                    new Alternate(
                        'en',
                        Set::of(Url::class, $en = Url::of('/en'))
                    )
                )
        );
        $second = new Alternates(
            Map::of('string', Attribute::class)
                (
                    'fr',
                    new Alternate(
                        'fr',
                        Set::of(Url::class, $fr = Url::of('/fr'))
                    )
                )
                (
                    'en',
                    new Alternate(
                        'en',
                        Set::of(Url::class, $bis = Url::of('/en/bis'))
                    )
                )
        );

        $third = $first->merge($second);

        $this->assertInstanceOf(Alternates::class, $third);
        $this->assertNotSame($first, $third);
        $this->assertNotSame($second, $third);
        $this->assertSame(
            ['de', 'en', 'fr'],
            unwrap($third->content()->keys()),
        );
        $this->assertSame(
            [$de],
            unwrap($third->content()->get('de')->content()),
        );
        $this->assertSame(
            [$en, $bis],
            unwrap($third->content()->get('en')->content()),
        );
        $this->assertSame(
            [$fr],
            unwrap($third->content()->get('fr')->content()),
        );
    }
}
