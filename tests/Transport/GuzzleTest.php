<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler\Transport;

use Innmind\Crawler\{
    Transport\Guzzle,
    Request,
    TransportInterface
};
use Innmind\Url\Url;
use Innmind\Http\{
    Translator\Response\Psr7Translator,
    Factory\Header\DefaultFactory,
    Factory\HeaderFactoryInterface,
    Message\ResponseInterface,
    Message\Method
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\Map;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;

class GuzzleTest extends \PHPUnit_Framework_TestCase
{
    public function testApply()
    {
        $transport = new Guzzle(
            $client = $this->createMock(ClientInterface::class),
            new Psr7Translator(
                new DefaultFactory(
                    new Map('string', HeaderFactoryInterface::class)
                )
            )
        );
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'http://example.com/',
                []
            )
            ->willReturn(
                $response = $this->createMock(Psr7ResponseInterface::class)
            );
        $response
            ->method('getProtocolVersion')
            ->willReturn('1.1');
        $response
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->method('getHeaders')
            ->willReturn([]);

        $response = $transport->apply(
            new Request(Url::fromString('http://example.com'))
        );

        $this->assertInstanceOf(TransportInterface::class, $transport);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testApplyWithMethod()
    {
        $transport = new Guzzle(
            $client = $this->createMock(ClientInterface::class),
            new Psr7Translator(
                new DefaultFactory(
                    new Map('string', HeaderFactoryInterface::class)
                )
            )
        );
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'http://example.com/',
                []
            )
            ->willReturn(
                $response = $this->createMock(Psr7ResponseInterface::class)
            );
        $response
            ->method('getProtocolVersion')
            ->willReturn('1.1');
        $response
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->method('getHeaders')
            ->willReturn([]);

        $response = $transport->apply(
            (new Request(Url::fromString('http://example.com')))
                ->withMethod(new Method('POST'))
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testApplyWithHeaders()
    {
        $transport = new Guzzle(
            $client = $this->createMock(ClientInterface::class),
            new Psr7Translator(
                new DefaultFactory(
                    new Map('string', HeaderFactoryInterface::class)
                )
            )
        );
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'http://example.com/',
                [
                    'headers' => ['Content-Type' => 'application/json'],
                ]
            )
            ->willReturn(
                $response = $this->createMock(Psr7ResponseInterface::class)
            );
        $response
            ->method('getProtocolVersion')
            ->willReturn('1.1');
        $response
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->method('getHeaders')
            ->willReturn([]);

        $response = $transport->apply(
            (new Request(Url::fromString('http://example.com')))
                ->withHeaders(
                    (new Map('string', 'string'))
                        ->put('Content-Type', 'application/json')
                )
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testApplyWithPayload()
    {
        $transport = new Guzzle(
            $client = $this->createMock(ClientInterface::class),
            new Psr7Translator(
                new DefaultFactory(
                    new Map('string', HeaderFactoryInterface::class)
                )
            )
        );
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'http://example.com/',
                [
                    'body' => 'content',
                ]
            )
            ->willReturn(
                $response = $this->createMock(Psr7ResponseInterface::class)
            );
        $response
            ->method('getProtocolVersion')
            ->willReturn('1.1');
        $response
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->method('getHeaders')
            ->willReturn([]);

        $response = $transport->apply(
            (new Request(Url::fromString('http://example.com')))
                ->withPayload(new StringStream('content'))
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testApplyCompletelyModifiedRequest()
    {
        $transport = new Guzzle(
            $client = $this->createMock(ClientInterface::class),
            new Psr7Translator(
                new DefaultFactory(
                    new Map('string', HeaderFactoryInterface::class)
                )
            )
        );
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'http://example.com/',
                [
                    'body' => 'content',
                    'headers' => ['Content-Type' => 'application/json'],
                ]
            )
            ->willReturn(
                $response = $this->createMock(Psr7ResponseInterface::class)
            );
        $response
            ->method('getProtocolVersion')
            ->willReturn('1.1');
        $response
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->method('getHeaders')
            ->willReturn([]);

        $response = $transport->apply(
            (new Request(Url::fromString('http://example.com')))
                ->withMethod(new Method('POST'))
                ->withPayload(new StringStream('content'))
                ->withHeaders(
                    (new Map('string', 'string'))
                        ->put('Content-Type', 'application/json')
                )
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
