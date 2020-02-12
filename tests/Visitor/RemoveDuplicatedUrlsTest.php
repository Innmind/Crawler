<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Visitor;

use Innmind\Crawler\Visitor\RemoveDuplicatedUrls;
use Innmind\Url\Url;
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class RemoveDuplicatedUrlsTest extends TestCase
{
    public function testInterface()
    {
        $urls = (new RemoveDuplicatedUrls)(
            Set::of(
                Url::class,
                $keep1 = Url::of('http://example.com/'),
                Url::of('http://example.com/'),
                $keep2 = Url::of('http://example.com/yay'),
                $keep3 = Url::of('http://example.com/bar/baz'),
                Url::of('http://example.com/bar/baz'),
                $keep4 = Url::of('http://example.com/unique')
            )
        );

        $this->assertInstanceOf(Set::class, $urls);
        $this->assertSame(Url::class, (string) $urls->type());
        $this->assertSame(
            [$keep1, $keep2, $keep3, $keep4],
            unwrap($urls),
        );
    }

    public function testThrowWhenInvalidSet()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Set<Innmind\Url\Url>');

        (new RemoveDuplicatedUrls)(Set::strings());
    }
}
