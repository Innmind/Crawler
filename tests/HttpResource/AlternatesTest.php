<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\HttpResource;

use Innmind\Crawler\HttpResource\{
    Alternates,
    Alternate,
    AttributeInterface,
    AttributesInterface
};
use Innmind\Url\{
    UrlInterface,
    Url
};
use Innmind\Immutable\{
    Map,
    Set
};
use PHPUnit\Framework\TestCase;

class AlternatesTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            AttributesInterface::class,
            $alternates = new Alternates(
                $map = new Map('string', AttributeInterface::class)
            )
        );
        $this->assertSame('alternates', $alternates->name());
        $this->assertSame($map, $alternates->content());
    }

    public function testIterator()
    {
        $alternates = new Alternates(
            (new Map('string', AttributeInterface::class))
                ->put(
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

    /**
     * @expectedException Innmind\Crawler\Exception\InvalidArgumentException
     */
    public function testThrowWhenNotOnlyAlternates()
    {
        new Alternates(
            (new Map('string', AttributeInterface::class))
                ->put(
                    'fr',
                    $this->createMock(AttributeInterface::class)
                )
        );
    }

    public function testMerge()
    {
        $first = new Alternates(
            (new Map('string', AttributeInterface::class))
                ->put(
                    'de',
                    new Alternate(
                        'de',
                        (new Set(UrlInterface::class))
                            ->add($de = Url::fromString('/de'))
                    )
                )
                ->put(
                    'en',
                    new Alternate(
                        'en',
                        (new Set(UrlInterface::class))
                            ->add($en = Url::fromString('/en'))
                    )
                )
        );
        $second = new Alternates(
            (new Map('string', AttributeInterface::class))
                ->put(
                    'fr',
                    new Alternate(
                        'fr',
                        (new Set(UrlInterface::class))
                            ->add($fr = Url::fromString('/fr'))
                    )
                )
                ->put(
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
