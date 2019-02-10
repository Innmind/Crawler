<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Crawler;

use Innmind\Crawler\{
    Crawler\Crawler,
    Crawler as CrawlerInterface,
    Parser,
    HttpResource,
    HttpResource\Attribute,
};
use Innmind\Http\{
    Message\Request\Request,
    Message\Response,
    Message\Method\Method,
    Headers\Headers,
    ProtocolVersion\ProtocolVersion,
    Header,
};
use Innmind\HttpTransport\Transport;
use Innmind\Filesystem\{
    MediaType\NullMediaType,
    MediaType\MediaType,
    Stream\StringStream,
};
use Innmind\Stream\Readable;
use Innmind\Url\Url;
use Innmind\Immutable\{
    Set,
    MapInterface,
    Map,
};
use PHPUnit\Framework\TestCase;

class CrawlerTest extends TestCase
{
    public function testInterface()
    {
        $crawler = new Crawler(
            $transport = $this->createMock(Transport::class),
            $parser = $this->createMock(Parser::class)
        );
        $request = new Request(
            $url = Url::fromString('http://example.com'),
            new Method('GET'),
            new ProtocolVersion(1, 1),
            new Headers(new Map('string', Header::class)),
            new StringStream('')
        );
        $transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->method('body')
            ->willReturn($content = $this->createMock(Readable::class));
        $parser
            ->expects($this->once())
            ->method('parse')
            ->with($request, $response)
            ->willReturn(
                (new Map('string', Attribute::class))
                    ->put('foo', $attribute = new Attribute\Attribute('foo', 42, 24))
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
        $this->assertSame(Attribute::class, (string) $attributes->valueType());
        $this->assertCount(1, $attributes);
        $this->assertSame($attribute, $attributes->get('foo'));
    }

    public function testUseTheParsedMediaTypeForTheResource()
    {
        $crawler = new Crawler(
            $transport = $this->createMock(Transport::class),
            $parser = $this->createMock(Parser::class)
        );
        $request = new Request(
            Url::fromString('http://example.com'),
            new Method('GET'),
            new ProtocolVersion(1, 1),
            new Headers(new Map('string', Header::class)),
            new StringStream('')
        );
        $transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->method('body')
            ->willReturn($this->createMock(Readable::class));
        $parser
            ->expects($this->once())
            ->method('parse')
            ->with($request, $response)
            ->willReturn(
                (new Map('string', Attribute::class))
                    ->put(
                        'content_type',
                        new Attribute\Attribute('content_type', 'application/json', 24)
                    )
            );

        $resource = $crawler->execute($request);

        $this->assertInstanceOf(MediaType::class, $resource->mediaType());
        $this->assertSame('application/json', (string) $resource->mediaType());
    }
}
