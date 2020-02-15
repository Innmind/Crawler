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
use Innmind\Colour\{
    Colour,
    Exception\Exception,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use function Innmind\Immutable\first;

final class ThemeColorParser implements Parser
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
            $meta = (new Elements('meta'))(
                Search::head()($document),
            )
                ->filter(static function(Element $meta): bool {
                    return $meta->attributes()->contains('name') &&
                        $meta->attributes()->contains('content');
                })
                ->filter(static function(Element $meta): bool {
                    $name = $meta
                        ->attributes()
                        ->get('name')
                        ->value();

                    return Str::of($name)->toLower()->toString() === 'theme-color';
                });
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        if ($meta->size() !== 1) {
            return $attributes;
        }

        try {
            $colour = Colour::of(
                first($meta)
                    ->attributes()
                    ->get('content')
                    ->value(),
            );
        } catch (Exception $e) {
            return $attributes;
        }

        return ($attributes)(
            self::key(),
            new Attribute(self::key(), $colour),
        );
    }

    public static function key(): string
    {
        return 'theme-color';
    }
}
