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
use Innmind\MediaType\MediaType;
use Innmind\Immutable\Map;

final class Crawler implements CrawlerInterface
{
    private Transport $fulfill;
    private Parser $parse;

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
            Map::of('string', Attribute::class)
        );

        if ($attributes->contains(ContentTypeParser::key())) {
            $mediaType = $attributes->get(ContentTypeParser::key())->content();
        } else {
            $mediaType = MediaType::null();
        }

        return new HttpResource(
            $request->url(),
            $mediaType,
            $attributes,
            $response->body()
        );
    }
}
