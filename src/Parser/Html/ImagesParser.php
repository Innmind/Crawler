<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
    UrlResolver,
    Visitor\RemoveDuplicatedUrls,
};
use Innmind\Xml\{
    Reader,
    Node,
    Element as ElementInterface,
    Visitor\Text,
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Element,
    Exception\ElementNotFound,
    Element\Img,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Map,
    Pair,
};

final class ImagesParser implements Parser
{
    private Reader $read;
    private UrlResolver $resolve;

    public function __construct(Reader $read, UrlResolver $resolve)
    {
        $this->read = $read;
        $this->resolve = $resolve;
    }

    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        $document = ($this->read)($response->body());

        try {
            $body = Element::body()($document);
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        $images = $this
            ->images($body)
            ->map(function(Url $url, string $description) use ($request, $attributes): Pair {
                return new Pair(
                    ($this->resolve)($request, $attributes, $url),
                    $description
                );
            });
        $figures = $this
            ->figures($body)
            ->map(function(Url $url, string $description) use ($request, $attributes): Pair {
                return new Pair(
                    ($this->resolve)($request, $attributes, $url),
                    $description
                );
            });

        $images = $this->removeDuplicates($images, $figures);

        if ($images->size() === 0) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), $images)
        );
    }

    public static function key(): string
    {
        return 'images';
    }

    private function images(ElementInterface $body): Map
    {
        return (new Elements('img'))($body)
            ->filter(static function(Node $img): bool {
                return $img instanceof Img;
            })
            ->reduce(
                Map::of(Url::class, 'string'),
                static function(Map $images, Img $img): Map {
                    return $images->put(
                        $img->src(),
                        $img->attributes()->contains('alt') ?
                            $img->attributes()->get('alt')->value() : ''
                    );
                }
            );
    }

    private function figures(ElementInterface $body): Map
    {
        return (new Elements('figure'))($body)
            ->filter(static function(Node $figure): bool {
                try {
                    $img = (new Element('img'))($figure);

                    return $img instanceof Img;
                } catch (ElementNotFound $e) {
                    return false;
                }
            })
            ->reduce(
                Map::of(Url::class, 'string'),
                static function(Map $images, ElementInterface $figure): Map {
                    $img = (new Element('img'))($figure);

                    try {
                        $caption = (new Element('figcaption'))($figure);

                        return $images->put(
                            $img->src(),
                            (new Text)($caption)
                        );
                    } catch (ElementNotFound $e) {
                        return $images->put(
                            $img->src(),
                            $img->attributes()->contains('alt') ?
                                $img->attributes()->get('alt')->value() : ''
                        );
                    }
                }
            );
    }

    private function removeDuplicates(Map $images, Map $figures): Map
    {
        $urls = $figures
            ->keys()
            ->merge($images->keys());
        $urls = (new RemoveDuplicatedUrls)($urls);

        return $figures
            ->merge($images)
            ->filter(static function(Url $url) use ($urls): bool {
                return $urls->contains($url);
            });
    }
}
