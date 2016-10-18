<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Parser\Http;

use Innmind\Crawler\{
    ParserInterface,
    Parser\Http\LanguagesParser,
    HttpResource\AttributeInterface
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PointInTimeInterface,
    ElapsedPeriod
};
use Innmind\Http\{
    Message\RequestInterface,
    Message\ResponseInterface,
    Header\HeaderInterface,
    Header\HeaderValueInterface,
    Header\ContentLanguage,
    Header\ContentLanguageValue,
    HeadersInterface
};
use Innmind\Immutable\{
    Map,
    Set,
    SetInterface
};

class LanguagesParserTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            ParserInterface::class,
            new LanguagesParser(
                $this->createMock(TimeContinuumInterface::class)
            )
        );
    }

    public function testKey()
    {
        $this->assertSame('languages', LanguagesParser::key());
    }

    public function testDoesntParseWhenNoContentLanguage()
    {
        $parser = new LanguagesParser(
            $this->createMock(TimeContinuumInterface::class)
        );
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                $headers = $this->createMock(HeadersInterface::class)
            );
        $headers
            ->expects($this->once())
            ->method('has')
            ->with('Content-Language')
            ->willReturn(false);
        $expected = new Map('string', AttributeInterface::class);

        $attributes = $parser->parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testDoesntParseWhenContentLanguageNotFullyParsed()
    {
        $parser = new LanguagesParser(
            $this->createMock(TimeContinuumInterface::class)
        );
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $headers = $this->createMock(HeadersInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('headers')
            ->willReturn($headers);
        $headers
            ->expects($this->once())
            ->method('has')
            ->with('Content-Language')
            ->willReturn(true);
        $headers
            ->expects($this->once())
            ->method('get')
            ->with('Content-Language')
            ->willReturn($this->createMock(HeaderInterface::class));
        $expected = new Map('string', AttributeInterface::class);

        $attributes = $parser->parse($request, $response, $expected);

        $this->assertSame($expected, $attributes);
    }

    public function testParse()
    {
        $parser = new LanguagesParser(
            $clock = $this->createMock(TimeContinuumInterface::class)
        );
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $headers = $this->createMock(HeadersInterface::class);
        $response
            ->expects($this->exactly(3))
            ->method('headers')
            ->willReturn($headers);
        $headers
            ->expects($this->once())
            ->method('has')
            ->with('Content-Language')
            ->willReturn(true);
        $headers
            ->expects($this->exactly(2))
            ->method('get')
            ->with('Content-Language')
            ->willReturn(
                new ContentLanguage(
                    (new Set(HeaderValueInterface::class))
                        ->add(new ContentLanguageValue('fr'))
                        ->add(new ContentLanguageValue('en-US'))
                )
            );
        $expected = new Map('string', AttributeInterface::class);
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
            ->willReturn(new ElapsedPeriod(42));

        $attributes = $parser->parse($request, $response, $expected);

        $this->assertNotSame($expected, $attributes);
        $this->assertCount(1, $attributes);
        $this->assertSame('languages', $attributes->key());
        $this->assertSame('languages', $attributes->current()->name());
        $this->assertInstanceOf(
            SetInterface::class,
            $attributes->current()->content()
        );
        $this->assertSame(
            ['fr', 'en-US'],
            $attributes->current()->content()->toPrimitive()
        );
        $this->assertSame(42, $attributes->current()->parsingTime());
    }
}
