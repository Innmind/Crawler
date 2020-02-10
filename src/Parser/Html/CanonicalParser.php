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

final class CanonicalParser implements Parser
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
        MapInterface $attributes
    ): MapInterface {
        $document = ($this->read)($response->body());

        try {
            $links = (new Elements('link'))(
                (new Head)($document)
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        $link = $links
            ->filter(static function(Node $link): bool {
                return $link instanceof Link;
            })
            ->filter(static function(Link $link): bool {
                return $link->relationship() === 'canonical';
            });

        if ($link->size() !== 1) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                ($this->resolve)(
                    $request,
                    $attributes,
                    $link->current()->href()
                )
            )
        );
    }

    public static function key(): string
    {
        return 'canonical';
    }
}
