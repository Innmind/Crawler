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

class RemoveDuplicatedUrlsTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $urls = (new RemoveDuplicatedUrls)(
            (new Set(UrlInterface::class))
                ->add($keep1 = Url::fromString('http://example.com/'))
                ->add(Url::fromString('http://example.com/'))
                ->add($keep2 = Url::fromString('http://example.com/yay'))
                ->add($keep3 = Url::fromString('http://example.com/bar/baz'))
                ->add(Url::fromString('http://example.com/bar/baz'))
                ->add($keep4 = Url::fromString('http://example.com/unique'))
        );

        $this->assertInstanceOf(SetInterface::class, $urls);
        $this->assertSame(UrlInterface::class, (string) $urls->type());
        $this->assertSame(
            [$keep1, $keep2, $keep3, $keep4],
            $urls->toPrimitive()
        );
    }

    /**
     * @expectedException Innmind\Crawler\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidSet()
    {
        (new RemoveDuplicatedUrls)(new Set('string'));
    }
}
