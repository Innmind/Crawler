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
use Innmind\Immutable\{
    MapInterface,
    Str,
};

final class AuthorParser implements Parser
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
            $metas = (new Elements('meta'))(
                (new Head)($document)
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        $meta = $metas
            ->filter(function(Element $meta): bool {
                return $meta->attributes()->contains('name') &&
                    $meta->attributes()->contains('content');
            })
            ->filter(function(Element $meta): bool {
                $name = $meta
                    ->attributes()
                    ->get('name')
                    ->value();

                return (string) Str::of($name)->toLower() === 'author';
            })
            ->filter(static function(Element $meta): bool {
                return !empty($meta->attributes()->get('content')->value());
            });

        if ($meta->size() !== 1) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                $meta
                    ->current()
                    ->attributes()
                    ->get('content')
                    ->value()
            )
        );
    }

    public static function key(): string
    {
        return 'author';
    }
}
