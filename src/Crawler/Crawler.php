<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Crawler;

use Innmind\Crawler\{
    Crawler as CrawlerInterface,
    HttpResource,
    Parser,
    HttpResource\Attribute,
    Parser\Http\ContentTypeParser,
};
use Innmind\Http\Message\Request;
use Innmind\HttpTransport\Transport;
use Innmind\Filesystem\MediaType\{
    MediaType,
    NullMediaType,
};
use Innmind\Immutable\Map;

final class Crawler implements CrawlerInterface
{
    private $fulfill;
    private $parse;

    public function __construct(
        Transport $fulfill,
        Parser $parse
    ) {
        $this->fulfill = $fulfill;
        $this->parse = $parse;
    }

    public function __invoke(Request $request): HttpResource
    {
        $response = ($this->fulfill)($request);
        $attributes = ($this->parse)(
            $request,
            $response,
            new Map('string', Attribute::class)
        );

        if ($attributes->contains(ContentTypeParser::key())) {
            $mediaType = MediaType::fromString(
                (string) $attributes->get(ContentTypeParser::key())->content()
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
