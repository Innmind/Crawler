<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Visitor;

use Innmind\Crawler\Visitor\RemoveDuplicatedUrls;
use Innmind\Url\{
    UrlInterface,
    Url
};
use Innmind\Immutable\{
    SetInterface,
    Set
};
use PHPUnit\Framework\TestCase;

class RemoveDuplicatedUrlsTest extends TestCase
{
    public function testInterface()
    {
        $urls = (new RemoveDuplicatedUrls)(
            Set::of(
                UrlInterface::class,
                $keep1 = Url::fromString('http://example.com/'),
                Url::fromString('http://example.com/'),
                $keep2 = Url::fromString('http://example.com/yay'),
                $keep3 = Url::fromString('http://example.com/bar/baz'),
                Url::fromString('http://example.com/bar/baz'),
                $keep4 = Url::fromString('http://example.com/unique')
            )
        );

        $this->assertInstanceOf(SetInterface::class, $urls);
        $this->assertSame(UrlInterface::class, (string) $urls->type());
        $this->assertSame(
            [$keep1, $keep2, $keep3, $keep4],
            $urls->toPrimitive()
        );
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 1 must be of type SetInterface<Innmind\Url\UrlInterface>
     */
    public function testThrowWhenInvalidSet()
    {
        (new RemoveDuplicatedUrls)(new Set('string'));
    }
}
