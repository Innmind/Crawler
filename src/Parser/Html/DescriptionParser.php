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
use Innmind\Immutable\{
    Map,
    Str,
};
use function Innmind\Immutable\first;

final class DescriptionParser implements Parser
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
                Search::head()($document)
            );
        } catch (ElementNotFound $e) {
            return $attributes;
        }

        $meta = $metas
            ->filter(static function(Element $meta): bool {
                return $meta->attributes()->contains('name') &&
                    $meta->attributes()->contains('content');
            })
            ->filter(static function(Element $meta): bool {
                $name = $meta
                    ->attributes()
                    ->get('name')
                    ->value();

                return Str::of($name)->toLower()->toString() === 'description';
            });

        if ($meta->size() !== 1) {
            return $attributes;
        }

        $description = first($meta)
            ->attributes()
            ->get('content')
            ->value();
        $description = Str::of($description)
            ->trim()
            ->pregReplace('/\t/m', ' ')
            ->pregReplace('/ {2,}/m', ' ');

        if ($description->length() > 150) {
            $description = $description
                ->substring(0, 150)
                ->append('...');
        }

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), $description->toString())
        );
    }

    public static function key(): string
    {
        return 'description';
    }
}
