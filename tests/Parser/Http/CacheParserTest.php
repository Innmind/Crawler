<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser\Http\CacheParser,
    Parser,
    HttpResource\Attribute,
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PointInTimeInterface,
    ElapsedPeriod,
    Period\Earth\Second,
};
use Innmind\Http\{
    Message\Request\Request,
    Message\Response,
    Message\Method\Method,
    Headers\Headers,
    ProtocolVersion\ProtocolVersion,
    Header\CacheControl,
    Header\CacheControlValue\PrivateCache,
    Header\CacheControlValue\PublicCache,
    Header\CacheControlValue\SharedMaxAge,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    MapInterface,
    Map,
};
use PHPUnit\Framework\TestCase;

class CaheParserTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Parser::class,
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
        $attributes = (new CacheParser($clock))(
            new Request(
                Url::fromString('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1)
            ),
            $response,
            $expected = new Map('string', Attribute::class)
        );

        $this->assertSame($expected, $attributes);
    }

    public function testSharedMaxAgeDirectiveNotFound()
    {
        $response = $this->createMock(Response::class);
        $response
            ->method('headers')
            ->willReturn(
                Headers::of(
                    new CacheControl(
                        new PrivateCache('')
                    )
                )
            );
        $clock = $this->createMock(TimeContinuumInterface::class);
        $attributes = (new CacheParser($clock))(
            new Request(
                Url::fromString('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1)
            ),
            $response,
            $expected = new Map('string', Attribute::class)
        );

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $response = $this->createMock(Response::class);
        $response
            ->method('headers')
            ->willReturn(
                Headers::of(
                    new CacheControl(
                        new PublicCache,
                        new SharedMaxAge(42)
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
        $attributes = (new CacheParser($clock))(
            new Request(
                Url::fromString('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1)
            ),
            $response,
            $notExpected = new Map('string', Attribute::class)
        );

        $this->assertNotSame($notExpected, $attributes);
        $this->assertInstanceOf(MapInterface::class, $attributes);
        $this->assertSame('string', (string) $attributes->keyType());
        $this->assertSame(Attribute::class, (string) $attributes->valueType());
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
