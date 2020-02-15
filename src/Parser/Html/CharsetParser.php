<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
};
use Innmind\Xml\{
    Reader,
    Element,
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Element as Search,
    Exception\ElementNotFound,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\Map;
use function Innmind\Immutable\first;

final class CharsetParser implements Parser
{
    private Reader $read;

    public function __construct(Reader $read)
    {
        $this->read = $read;
    }

    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        $document = ($this->read)($response->body());

        try {
            $metas = (new Elements('meta'))(
                Search::head()($document),
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        $meta = $metas->filter(static function(Element $meta): bool {
            return $meta->attributes()->contains('charset');
        });

        if ($meta->size() !== 1) {
            return $attributes;
        }

        return ($attributes)(
            self::key(),
            new Attribute(
                self::key(),
                first($meta)
                    ->attributes()
                    ->get('charset')
                    ->value(),
            ),
        );
    }

    public static function key(): string
    {
        return 'charset';
    }
}
