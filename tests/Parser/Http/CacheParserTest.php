<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    Parser\Http\CacheParser,
    Parser,
    HttpResource\Attribute,
};
use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
    Earth\ElapsedPeriod,
    Earth\Period\Second,
};
use Innmind\Http\{
    Message\Request\Request,
    Message\Response,
    Message\Method,
    Headers,
    ProtocolVersion,
    Header\CacheControl,
    Header\CacheControlValue\PrivateCache,
    Header\CacheControlValue\PublicCache,
    Header\CacheControlValue\SharedMaxAge,
};
use Innmind\Url\Url;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class CacheParserTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Parser::class,
            new CacheParser(
                $this->createMock(Clock::class)
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
        $clock = $this->createMock(Clock::class);
        $attributes = (new CacheParser($clock))(
            new Request(
                Url::of('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1)
            ),
            $response,
            $expected = Map::of('string', Attribute::class)
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
        $clock = $this->createMock(Clock::class);
        $attributes = (new CacheParser($clock))(
            new Request(
                Url::of('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1)
            ),
            $response,
            $expected = Map::of('string', Attribute::class)
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
        $clock = $this->createMock(Clock::class);
        $clock
            ->expects($this->once())
            ->method('now')
            ->will(
                $this->onConsecutiveCalls(
                    $directive = $this->createMock(PointInTime::class)
                )
            );
        $directive
            ->expects($this->once())
            ->method('goForward')
            ->with($this->callback(static function(Second $second) {
                return $second->seconds() === 42;
            }))
            ->willReturn(
                $expected = $this->createMock(PointInTime::class)
            );
        $attributes = (new CacheParser($clock))(
            new Request(
                Url::of('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1)
            ),
            $response,
            $notExpected = Map::of('string', Attribute::class)
        );

        $this->assertNotSame($notExpected, $attributes);
        $this->assertInstanceOf(Map::class, $attributes);
        $this->assertSame('string', (string) $attributes->keyType());
        $this->assertSame(Attribute::class, (string) $attributes->valueType());
        $this->assertCount(1, $attributes);
        $attribute = $attributes->get('expires_at');
        $this->assertSame('expires_at', $attribute->name());
        $this->assertInstanceOf(PointInTime::class, $attribute->content());
        $this->assertSame(
            $expected,
            $attribute->content()
        );
    }
}
