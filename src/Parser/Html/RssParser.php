<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
    UrlResolver,
};
use Innmind\Xml\{
    Reader,
    Node,
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Head,
    Exception\ElementNotFound,
    Element\Link,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\MapInterface;

final class RssParser implements Parser
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
            $links = (new Elements('link'))(
                (new Head)($document)
            )
                ->filter(function(Node $link): bool {
                    return $link instanceof Link;
                })
                ->filter(function(Link $link): bool {
                    return $link->relationship() === 'alternate' &&
                        $link->attributes()->contains('type') &&
                        $link->attributes()->get('type')->value() === 'application/rss+xml';
                });
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        if ($links->size() !== 1) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                $this->resolver->resolve(
                    $request,
                    $attributes,
                    $links->current()->href()
                )
            )
        );
    }

    public static function key(): string
    {
        return 'rss';
    }
}
