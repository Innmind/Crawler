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

final class DescriptionParser implements Parser
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

                return (string) (new Str($name))->toLower() === 'description';
            });

        if ($meta->size() !== 1) {
            return $attributes;
        }

        $description = $meta
            ->current()
            ->attributes()
            ->get('content')
            ->value();
        $description = (new Str($description))
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
            new Attribute(self::key(), (string) $description)
        );
    }

    public static function key(): string
    {
        return 'description';
    }
}
