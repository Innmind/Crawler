<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser\Http\CacheParser,
    ParserInterface,
    HttpResource\AttributeInterface
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PointInTimeInterface,
    ElapsedPeriod
};
use Innmind\Http\{
    Message\Request,
    Message\ResponseInterface,
    Message\Method,
    Headers,
    ProtocolVersion,
    Header\HeaderInterface,
    Header\HeaderValueInterface,
    Header\CacheControl,
    Header\CacheControlValue\PrivateCache,
    Header\CacheControlValue\PublicCache,
    Header\CacheControlValue\SharedMaxAge
};
use Innmind\Url\Url;
use Innmind\Filesystem\Stream\StringStream;
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
            new CacheParser(
                $this->createMock(TimeContinuumInterface::class)
            )
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
        $clock = $this->createMock(TimeContinuumInterface::class);
        $attributes = (new CacheParser($clock))->parse(
            new Request(
                Url::fromString('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers(new Map('string', HeaderInterface::class)),
                new StringStream('')
            ),
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
        $clock = $this->createMock(TimeContinuumInterface::class);
        $attributes = (new CacheParser($clock))->parse(
            new Request(
                Url::fromString('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers(new Map('string', HeaderInterface::class)),
                new StringStream('')
            ),
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
        $clock = $this->createMock(TimeContinuumInterface::class);
        $clock
            ->expects($this->exactly(2))
            ->method('now')
            ->will(
                $this->onConsecutiveCalls(
                    $start = $this->createMock(PointInTimeInterface::class),
                    $end = $this->createMock(PointInTimeInterface::class)
                )
            );
        $end
            ->expects($this->once())
            ->method('elapsedSince')
            ->with($start)
            ->willReturn(new ElapsedPeriod(24));
        $attributes = (new CacheParser($clock))->parse(
            new Request(
                Url::fromString('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers(new Map('string', HeaderInterface::class)),
                new StringStream('')
            ),
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
        $this->assertSame(24, $attribute->parsingTime());
    }
}
