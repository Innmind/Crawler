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
    Visitor\Body,
    Exception\ElementNotFound,
    Element\Img,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Pair,
};

final class ImagesParser implements Parser
{
    use HtmlTrait;

    private $read;
    private $resolver;

    public function __construct(Reader $read, UrlResolver $resolver)
    {
        $this->read = $read;
        $this->resolver = $resolver;
    }

    public function parse(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        if (!$this->isHtml($attributes)) {
            return $attributes;
        }

        $document = ($this->read)($response->body());

        try {
            $body = (new Body)($document);
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        $images = $this
            ->images($body)
            ->map(function(UrlInterface $url, string $description) use ($request, $attributes): Pair {
                return new Pair(
                    $this->resolver->resolve($request, $attributes, $url),
                    $description
                );
            });
        $figures = $this
            ->figures($body)
            ->map(function(UrlInterface $url, string $description) use ($request, $attributes): Pair {
                return new Pair(
                    $this->resolver->resolve($request, $attributes, $url),
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
            ->filter(function(Node $img): bool {
                return $img instanceof Img;
            })
            ->reduce(
                new Map(UrlInterface::class, 'string'),
                function(MapInterface $images, Img $img): MapInterface {
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
            ->filter(function(Node $figure): bool {
                try {
                    $img = (new Element('img'))($figure);

                    return $img instanceof Img;
                } catch (ElementNotFound $e) {
                    return false;
                }
            })
            ->reduce(
                new Map(UrlInterface::class, 'string'),
                function(MapInterface $images, ElementInterface $figure): MapInterface {
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

    private function removeDuplicates(MapInterface $images, MapInterface $figures): MapInterface
    {
        $urls = $figures
            ->keys()
            ->merge($images->keys());
        $urls = (new RemoveDuplicatedUrls)($urls);

        return $figures
            ->merge($images)
            ->filter(function(UrlInterface $url) use ($urls): bool {
                return $urls->contains($url);
            });
    }
}
