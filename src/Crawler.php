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
use Innmind\Immutable\{
    SetInterface,
    Map
};

final class Crawler implements CrawlerInterface
{
    private $http;
    private $parsers;

    public function __construct(
        TransportInterface $http,
        SetInterface $parsers
    ) {
        if ((string) $parsers->type() !== ParserInterface::class) {
            throw new InvalidArgumentException;
        }

        $this->http = $http;
        $this->parsers = $parsers;
    }

    public function execute(RequestInterface $request): HttpResource
    {
        $response = $this->http->apply($request);
        $attributes = $this->parsers->reduce(
            new Map('string', AttributeInterface::class),
            function(Map $attributes, ParserInterface $parser) use ($request, $response): Map {
                return $attributes->merge(
                    $parser->parse($request, $response, $attributes)
                );
            }
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
