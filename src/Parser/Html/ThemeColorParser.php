<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute
};
use Innmind\Xml\{
    ReaderInterface,
    NodeInterface,
    ElementInterface
};
use Innmind\Html\{
    Visitor\Elements,
    Visitor\Head,
    Exception\ElementNotFoundException
};
use Innmind\Http\Message\{
    Request,
    Response
};
use Innmind\Colour\{
    Colour,
    Exception\ExceptionInterface
};
use Innmind\Immutable\{
    MapInterface,
    Str
};

final class ThemeColorParser implements Parser
{
    use HtmlTrait;

    private $reader;

    public function __construct(ReaderInterface $reader)
    {
        $this->reader = $reader;
    }

    public function parse(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        if (!$this->isHtml($attributes)) {
            return $attributes;
        }

        $document = $this->reader->read($response->body());

        try {
            $meta = (new Elements('meta'))(
                (new Head)($document)
            )
                ->filter(function(ElementInterface $meta): bool {
                    return $meta->attributes()->contains('name') &&
                        $meta->attributes()->contains('content');
                })
                ->filter(function(ElementInterface $meta): bool {
                    $name = $meta
                        ->attributes()
                        ->get('name')
                        ->value();

                    return (string) (new Str($name))->toLower() === 'theme-color';
                });
        } catch (ElementNotFoundException $e) {
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
