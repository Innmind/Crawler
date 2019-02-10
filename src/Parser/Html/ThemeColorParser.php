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
    Visitor\Head,
    Exception\ElementNotFound,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Colour\{
    Colour,
    Exception\ExceptionInterface,
};
use Innmind\Immutable\{
    MapInterface,
    Str,
};

final class ThemeColorParser implements Parser
{
    private $read;

    public function __construct(Reader $read)
    {
        $this->read = $read;
    }

    public function __invoke(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        $document = ($this->read)($response->body());

        try {
            $meta = (new Elements('meta'))(
                (new Head)($document)
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

                    return (string) Str::of($name)->toLower() === 'theme-color';
                });
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        if ($meta->size() !== 1) {
            return $attributes;
        }

        try {
            $colour = Colour::fromString(
                $meta
                    ->current()
                    ->attributes()
                    ->get('content')
                    ->value()
            );
        } catch (ExceptionInterface $e) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), $colour)
        );
    }

    public static function key(): string
    {
        return 'theme-color';
    }
}
