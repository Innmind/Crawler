<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
    Visitor\Html\OpenGraph,
};
use Innmind\Xml\Reader;
use Innmind\Url\Url;
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\{
    Map,
    Set,
    Str,
};

final class ImageParser implements Parser
{
    private Reader $read;
    private OpenGraph $extract;

    public function __construct(Reader $read)
    {
        $this->read = $read;
        $this->extract = new OpenGraph('image');
    }

    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        $document = ($this->read)($response->body());

        $values = ($this->extract)($document)
            ->filter(static function(string $url): bool {
                return !Str::of($url)->empty();
            })
            ->mapTo(
                Url::class,
                static fn(string $url): Url => Url::of($url),
            );

        if ($values->empty()) {
            return $attributes;
        }

        return ($attributes)(
            self::key(),
            new Attribute(self::key(), $values),
        );
    }

    public static function key(): string
    {
        return 'preview';
    }
}
