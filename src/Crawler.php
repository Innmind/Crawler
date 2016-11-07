<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Crawler\{
    HttpResource\AttributeInterface,
    Exception\InvalidArgumentException
};
use Innmind\Http\Message\RequestInterface;
use Innmind\Filesystem\MediaType\{
    MediaType,
    NullMediaType
};
use Innmind\Immutable\Map;

final class Crawler implements CrawlerInterface
{
    private $transport;
    private $parser;

    public function __construct(
        TransportInterface $transport,
        ParserInterface $parser
    ) {
        $this->transport = $transport;
        $this->parser = $parser;
    }

    public function execute(RequestInterface $request): HttpResource
    {
        $response = $this->transport->fulfill($request);
        $attributes = $this->parser->parse(
            $request,
            $response,
            new Map('string', AttributeInterface::class)
        );

        if ($attributes->contains('content_type')) {
            $mediaType = MediaType::fromString(
                (string) $attributes->get('content_type')->content()
            );
        } else {
            $mediaType = new NullMediaType;
        }

        return new HttpResource(
            $request->url(),
            $mediaType,
            $attributes,
            $response->body()
        );
    }
}
