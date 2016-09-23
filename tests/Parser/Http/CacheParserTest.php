<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser\Http\CacheParser,
    ParserInterface,
    Request,
    HttpResource\AttributeInterface
};
use Innmind\Http\{
    Message\ResponseInterface,
    Headers,
    Header\HeaderInterface,
    Header\HeaderValueInterface,
    Header\CacheControl,
    Header\CacheControlValue\PrivateCache,
    Header\CacheControlValue\PublicCache,
    Header\CacheControlValue\SharedMaxAge
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Map,
    Set,
    MapInterface
};

class CaheParserTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            ParserInterface::class,
            new CacheParser
        );
    }

    public function testDoesntHaveCacheControl()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('headers')
            ->willReturn(
                new Headers(
                    new Map('string', HeaderInterface::class)
                )
            );
        $attributes = (new CacheParser)->parse(
            new Request(Url::fromString('http://example.com')),
            $response,
            $expected = new Map('string', AttributeInterface::class)
        );

        $this->assertSame($expected, $attributes);
    }

    public function testSharedMaxAgeDirectiveNotFound()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'cache-control',
                            new CacheControl(
                                (new Set(HeaderValueInterface::class))
                                    ->add(new PrivateCache(''))
                            )
                        )
                )
            );
        $attributes = (new CacheParser)->parse(
            new Request(Url::fromString('http://example.com')),
            $response,
            $expected = new Map('string', AttributeInterface::class)
        );

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'cache-control',
                            new CacheControl(
                                (new Set(HeaderValueInterface::class))
                                    ->add(new PublicCache)
                                    ->add(new SharedMaxAge(42))
                            )
                        )
                )
            );
        $attributes = (new CacheParser)->parse(
            new Request(Url::fromString('http://example.com')),
            $response,
            $expected = new Map('string', AttributeInterface::class)
        );

        $this->assertNotSame($expected, $attributes);
        $this->assertInstanceOf(MapInterface::class, $attributes);
        $this->assertSame('string', (string) $attributes->keyType());
        $this->assertSame(AttributeInterface::class, (string) $attributes->valueType());
        $this->assertCount(1, $attributes);
        $attribute = $attributes->get('expires_at');
        $this->assertSame('expires_at', $attribute->name());
        $this->assertInstanceOf(\DateTimeImmutable::class, $attribute->content());
        $this->assertEquals(
            (new \DateTimeImmutable('+42 seconds')),
            $attribute->content()
        );
    }
}
