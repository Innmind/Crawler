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
    Message\Method,
    ProtocolVersion,
};
use Innmind\HttpTransport\Transport;
use Innmind\MediaType\MediaType;
use Innmind\Stream\Readable;
use Innmind\Url\Url;
use Innmind\Immutable\{
    Set,
    Map,
};
use PHPUnit\Framework\TestCase;

class CrawlerTest extends TestCase
{
    public function testInterface()
    {
        $crawl = new Crawler(
            $transport = $this->createMock(Transport::class),
            $parser = $this->createMock(Parser::class)
        );
        $request = new Request(
            $url = Url::of('http://example.com'),
            new Method('GET'),
            new ProtocolVersion(1, 1)
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
            ->method('__invoke')
            ->with($request, $response)
            ->willReturn(
                Map::of('string', Attribute::class)
                    ('foo', $attribute = new Attribute\Attribute('foo', 42, 24))
            );

        $resource = $crawl($request);

        $this->assertInstanceOf(CrawlerInterface::class, $crawl);
        $this->assertInstanceOf(HttpResource::class, $resource);
        $this->assertSame($url, $resource->url());
        $this->assertEquals(MediaType::null(), $resource->mediaType());
        $this->assertSame($content, $resource->content());
        $attributes = $resource->attributes();
        $this->assertInstanceOf(Map::class, $attributes);
        $this->assertSame('string', (string) $attributes->keyType());
        $this->assertSame(Attribute::class, (string) $attributes->valueType());
        $this->assertCount(1, $attributes);
        $this->assertSame($attribute, $attributes->get('foo'));
    }

    public function testUseTheParsedMediaTypeForTheResource()
    {
        $crawl = new Crawler(
            $transport = $this->createMock(Transport::class),
            $parser = $this->createMock(Parser::class)
        );
        $request = new Request(
            Url::of('http://example.com'),
            new Method('GET'),
            new ProtocolVersion(1, 1)
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
            ->method('__invoke')
            ->with($request, $response)
            ->willReturn(
                Map::of('string', Attribute::class)
                    (
                        'content_type',
                        new Attribute\Attribute(
                            'content_type',
                            $expected = MediaType::of('application/json')
                        )
                    )
            );

        $resource = $crawl($request);

        $this->assertSame($expected, $resource->mediaType());
    }
}
