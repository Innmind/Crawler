<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler;

use Innmind\Crawler\{
    Crawler,
    CrawlerInterface,
    TransportInterface,
    ParserInterface,
    HttpResource,
    HttpResource\Attribute,
    HttpResource\AttributeInterface
};
use Innmind\Http\{
    Message\Request,
    Message\ResponseInterface,
    Message\Method,
    Headers,
    ProtocolVersion,
    Header\HeaderInterface
};
use Innmind\Filesystem\{
    StreamInterface,
    MediaType\NullMediaType,
    MediaType\MediaType,
    Stream\StringStream
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Set,
    Map,
    MapInterface
};

class CrawlerTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $crawler = new Crawler(
            $transport = $this->createMock(TransportInterface::class),
            (new Set(ParserInterface::class))->add(
                $parser = $this->createMock(ParserInterface::class)
            )
        );
        $request = new Request(
            $url = Url::fromString('http://example.com'),
            new Method('GET'),
            new ProtocolVersion(1, 1),
            new Headers(new Map('string', HeaderInterface::class)),
            new StringStream('')
        );
        $transport
            ->expects($this->once())
            ->method('fulfill')
            ->with($request)
            ->willReturn(
                $response = $this->createMock(ResponseInterface::class)
            );
        $response
            ->method('body')
            ->willReturn($content = $this->createMock(StreamInterface::class));
        $parser
            ->expects($this->once())
            ->method('parse')
            ->with($request, $response)
            ->willReturn(
                (new Map('string', AttributeInterface::class))
                    ->put('foo', $attribute = new Attribute('foo', 42, 24))
            );

        $resource = $crawler->execute($request);

        $this->assertInstanceOf(CrawlerInterface::class, $crawler);
        $this->assertInstanceOf(HttpResource::class, $resource);
        $this->assertSame($url, $resource->url());
        $this->assertInstanceOf(NullMediaType::class, $resource->mediaType());
        $this->assertSame($content, $resource->content());
        $attributes = $resource->attributes();
        $this->assertInstanceOf(MapInterface::class, $attributes);
        $this->assertSame('string', (string) $attributes->keyType());
        $this->assertSame(AttributeInterface::class, (string) $attributes->valueType());
        $this->assertCount(1, $attributes);
        $this->assertSame($attribute, $attributes->get('foo'));
    }

    /**
     * @expectedException Innmind\Crawler\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidParserSet()
    {
        new Crawler(
            $this->createMock(TransportInterface::class),
            new Set('object')
        );
    }

    public function testUseTheParsedMediaTypeForTheResource()
    {
        $crawler = new Crawler(
            $transport = $this->createMock(TransportInterface::class),
            (new Set(ParserInterface::class))->add(
                $parser = $this->createMock(ParserInterface::class)
            )
        );
        $request = new Request(
            Url::fromString('http://example.com'),
            new Method('GET'),
            new ProtocolVersion(1, 1),
            new Headers(new Map('string', HeaderInterface::class)),
            new StringStream('')
        );
        $transport
            ->expects($this->once())
            ->method('fulfill')
            ->with($request)
            ->willReturn(
                $response = $this->createMock(ResponseInterface::class)
            );
        $response
            ->method('body')
            ->willReturn($this->createMock(StreamInterface::class));
        $parser
            ->expects($this->once())
            ->method('parse')
            ->with($request, $response)
            ->willReturn(
                (new Map('string', AttributeInterface::class))
                    ->put(
                        'content_type',
                        new Attribute('content_type', 'application/json', 24)
                    )
            );

        $resource = $crawler->execute($request);

        $this->assertInstanceOf(MediaType::class, $resource->mediaType());
        $this->assertSame('application/json', (string) $resource->mediaType());
    }
}
