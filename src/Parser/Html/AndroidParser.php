<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
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

final class AndroidParser implements Parser
{
    use HtmlTrait;

    private $read;

    public function __construct(Reader $read)
    {
        $this->read = $read;
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
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        $link = $links
            ->filter(static function(Node $link): bool {
                return $link instanceof Link;
            })
            ->filter(static function(Link $link): bool {
                return (string) $link->href()->scheme() === 'android-app';
            });

        if ($link->size() !== 1) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), $link->current()->href())
        );
    }

    public static function key(): string
    {
        return 'android';
    }
}
