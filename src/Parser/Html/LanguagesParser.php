<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
};
use Innmind\Xml\{
    Reader,
    Element as ElementInterface,
    Attribute as AttributeInterface,
};
use Innmind\Html\{
    Visitor\Element,
    Visitor\Elements,
    Exception\ElementNotFound,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\{
    Map,
    Str,
    Set,
};
use function Innmind\Immutable\first;

final class LanguagesParser implements Parser
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
        $languages = null;

        $document = ($this->read)($response->body());

        try {
            $html = (new Element('html'))($document);

            if ($html->attributes()->contains('lang')) {
                $languages = $html->attributes()->get('lang');
            }
        } catch (ElementNotFound $e) {
            //pass
        }

        if (!$languages instanceof AttributeInterface) {
            try {
                $metas = (new Elements('meta'))(
                    Element::head()($document)
                )
                    ->filter(static function(ElementInterface $element): bool {
                        return $element->attributes()->contains('http-equiv') &&
                            $element->attributes()->contains('content');
                    })
                    ->filter(static function(ElementInterface $meta): bool {
                        $header = $meta->attributes()->get('http-equiv')->value();
                        $header = Str::of($header);

                        return $header->toLower()->toString() === 'content-language';
                    });

                if ($metas->size() === 1) {
                    $languages = first($metas)
                        ->attributes()
                        ->get('content');
                }
            } catch (ElementNotFound $e) {
                //pass
            }
        }

        if (!$languages instanceof AttributeInterface) {
            return $attributes;
        }

        $languages = $this->parseAttribute($languages);

        if ($languages->empty()) {
            return $attributes;
        }

        return ($attributes)(
            self::key(),
            new Attribute(self::key(), $languages),
        );
    }

    public static function key(): string
    {
        return 'languages';
    }

    private function parseAttribute(AttributeInterface $languages): Set
    {
        $set = Set::strings();
        $languages = \explode(',', $languages->value());

        foreach ($languages as $language) {
            $language = Str::of($language)->trim();

            if ($language->matches('~^[a-zA-Z0-9]+(-[a-zA-Z0-9]+)*$~')) {
                $set = ($set)($language->toString());
            }
        }

        return $set;
    }
}
