<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
    Parser\Html\HtmlTrait,
    Visitor\Html\OpenGraph,
};
use Innmind\Xml\Reader;
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Set,
    Str,
};

final class ImageParser implements Parser
{
    use HtmlTrait;

    private $read;
    private $extract;

    public function __construct(Reader $read)
    {
        $this->read = $read;
        $this->extract = new OpenGraph('image');
    }

    public function __invoke(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        if (!$this->isHtml($attributes)) {
            return $attributes;
        }

        $document = ($this->read)($response->body());

        $values = ($this->extract)($document)
            ->filter(static function(string $url): bool {
                return !Str::of($url)->empty();
            })
            ->reduce(
                new Set(UrlInterface::class),
                static function(SetInterface $urls, string $url): SetInterface {
                    return $urls->add(Url::fromString($url));
                }
            );

        if ($values->empty()) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), $values)
        );
    }

    public static function key(): string
    {
        return 'preview';
    }
}
