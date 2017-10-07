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
    ElapsedPeriod,
    Period\Earth\Second
};
use Innmind\Http\{
    Message\Request\Request,
    Message\Response,
    Message\Method\Method,
    Headers\Headers,
    ProtocolVersion\ProtocolVersion,
    Header,
    Header\CacheControl,
    Header\CacheControlValue\PrivateCache,
    Header\CacheControlValue\PublicCache,
    Header\CacheControlValue\SharedMaxAge
};
use Innmind\Url\Url;
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    Map,
    MapInterface
};
use PHPUnit\Framework\TestCase;

class CaheParserTest extends TestCase
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

    public function testKey()
    {
        $this->assertSame('expires_at', CacheParser::key());
    }

    public function testDoesntHaveCacheControl()
    {
        $response = $this->createMock(Response::class);
        $response
            ->method('headers')
            ->willReturn(new Headers);
        $clock = $this->createMock(TimeContinuumInterface::class);
        $attributes = (new CacheParser($clock))->parse(
            new Request(
                Url::fromString('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers,
                new StringStream('')
            ),
            $response,
            $expected = new Map('string', AttributeInterface::class)
        );

        $this->assertSame($expected, $attributes);
    }

    public function testSharedMaxAgeDirectiveNotFound()
    {
        $response = $this->createMock(Response::class);
        $response
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', Header::class))
                        ->put(
                            'cache-control',
                            new CacheControl(
                                new PrivateCache('')
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
                new Headers,
                new StringStream('')
            ),
            $response,
            $expected = new Map('string', AttributeInterface::class)
        );

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $response = $this->createMock(Response::class);
        $response
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', Header::class))
                        ->put(
                            'cache-control',
                            new CacheControl(
                                new PublicCache,
                                new SharedMaxAge(42)
                            )
                        )
                )
            );
        $clock = $this->createMock(TimeContinuumInterface::class);
        $clock
            ->expects($this->once())
            ->method('now')
            ->will(
                $this->onConsecutiveCalls(
                    $directive = $this->createMock(PointInTimeInterface::class)
                )
            );
        $directive
            ->expects($this->once())
            ->method('goForward')
            ->with($this->callback(function(Second $second) {
                return $second->seconds() === 42;
            }))
            ->willReturn(
                $expected = $this->createMock(PointInTimeInterface::class)
            );
        $attributes = (new CacheParser($clock))->parse(
            new Request(
                Url::fromString('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers,
                new StringStream('')
            ),
            $response,
            $notExpected = new Map('string', AttributeInterface::class)
        );

        $this->assertNotSame($notExpected, $attributes);
        $this->assertInstanceOf(MapInterface::class, $attributes);
        $this->assertSame('string', (string) $attributes->keyType());
        $this->assertSame(AttributeInterface::class, (string) $attributes->valueType());
        $this->assertCount(1, $attributes);
        $attribute = $attributes->get('expires_at');
        $this->assertSame('expires_at', $attribute->name());
        $this->assertInstanceOf(PointInTimeInterface::class, $attribute->content());
        $this->assertSame(
            $expected,
            $attribute->content()
        );
    }
}
