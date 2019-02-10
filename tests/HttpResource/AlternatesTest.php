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
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class AlternatesTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Attributes::class,
            $alternates = new Alternates(
                $map = new Map('string', Attribute::class)
            )
        );
        $this->assertSame('alternates', $alternates->name());
        $this->assertSame($map, $alternates->content());
    }

    public function testIterator()
    {
        $alternates = new Alternates(
            Map::of('string', Attribute::class)
                (
                    'fr',
                    $alternate = new Alternate(
                        'fr',
                        new Set(UrlInterface::class)
                    )
                )
        );

        $this->assertSame($alternate, $alternates->current());
        $this->assertSame('fr', $alternates->key());
        $this->assertTrue($alternates->valid());
        $this->assertNull($alternates->next());
        $this->assertFalse($alternates->valid());
        $this->assertNull($alternates->rewind());
        $this->assertSame($alternate, $alternates->current());
        $this->assertSame('fr', $alternates->key());
    }

    public function testThrowWhenNotOnlyAlternates()
    {
        $this->expectException(InvalidArgumentException::class);

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
                        (new Set(UrlInterface::class))
                            ->add($de = Url::fromString('/de'))
                    )
                )
                (
                    'en',
                    new Alternate(
                        'en',
                        (new Set(UrlInterface::class))
                            ->add($en = Url::fromString('/en'))
                    )
                )
        );
        $second = new Alternates(
            Map::of('string', Attribute::class)
                (
                    'fr',
                    new Alternate(
                        'fr',
                        (new Set(UrlInterface::class))
                            ->add($fr = Url::fromString('/fr'))
                    )
                )
                (
                    'en',
                    new Alternate(
                        'en',
                        (new Set(UrlInterface::class))
                            ->add($bis = Url::fromString('/en/bis'))
                    )
                )
        );

        $third = $first->merge($second);

        $this->assertInstanceOf(Alternates::class, $third);
        $this->assertNotSame($first, $third);
        $this->assertNotSame($second, $third);
        $this->assertSame(
            ['de', 'en', 'fr'],
            $third->content()->keys()->toPrimitive()
        );
        $this->assertSame(
            [$de],
            $third->content()->get('de')->content()->toPrimitive()
        );
        $this->assertSame(
            [$en, $bis],
            $third->content()->get('en')->content()->toPrimitive()
        );
        $this->assertSame(
            [$fr],
            $third->content()->get('fr')->content()->toPrimitive()
        );
    }
}
