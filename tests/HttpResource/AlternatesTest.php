<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\HttpResource;

use Innmind\Crawler\HttpResource\{
    Alternates,
    Alternate,
    AttributeInterface,
    AttributesInterface
};
use Innmind\Immutable\{
    Map,
    Set
};

class AlternatesTest extends \PHPUnit_Framework_TestCase
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
                        (new Set('string'))->add('/de'),
                        0
                    )
                )
                ->put(
                    'en',
                    new Alternate(
                        'en',
                        (new Set('string'))->add('/en'),
                        0
                    )
                )
        );
        $second = new Alternates(
            (new Map('string', AttributeInterface::class))
                ->put(
                    'fr',
                    new Alternate(
                        'fr',
                        (new Set('string'))->add('/fr'),
                        0
                    )
                )
                ->put(
                    'en',
                    new Alternate(
                        'en',
                        (new Set('string'))->add('/en/bis'),
                        0
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
            ['/de'],
            $third->content()->get('de')->content()->toPrimitive()
        );
        $this->assertSame(
            ['/en', '/en/bis'],
            $third->content()->get('en')->content()->toPrimitive()
        );
        $this->assertSame(
            ['/fr'],
            $third->content()->get('fr')->content()->toPrimitive()
        );
    }
}
