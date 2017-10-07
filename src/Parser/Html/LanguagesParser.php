<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html;

use Innmind\Crawler\{
    ParserInterface,
    HttpResource\Attribute
};
use Innmind\Xml\{
    ReaderInterface,
    NodeInterface,
    ElementInterface,
    AttributeInterface
};
use Innmind\Html\{
    Visitor\Element,
    Visitor\Elements,
    Visitor\Head,
    Exception\ElementNotFoundException
};
use Innmind\Http\Message\{
    Request,
    Response
};
use Innmind\Immutable\{
    MapInterface,
    Str,
    Set
};

final class LanguagesParser implements ParserInterface
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
        $languages = null;

        if (!$this->isHtml($attributes)) {
            return $attributes;
        }

        $document = $this->reader->read($response->body());

        try {
            $html = (new Element('html'))($document);

            if ($html->attributes()->contains('lang')) {
                $languages = $html->attributes()->get('lang');
            }
        } catch (ElementNotFoundException $e) {
            //pass
        }

        if (!$languages instanceof AttributeInterface) {
            try {
                $metas = (new Elements('meta'))(
                    (new Head)($document)
                )
                    ->filter(function(ElementInterface $element): bool {
                        return $element->attributes()->contains('http-equiv') &&
                            $element->attributes()->contains('content');
                    })
                    ->filter(function(ElementInterface $meta): bool {
                        $header = $meta->attributes()->get('http-equiv')->value();
                        $header = new Str($header);

                        return (string) $header->toLower() === 'content-language';
                    });

                if ($metas->size() === 1) {
                    $languages = $metas
                        ->current()
                        ->attributes()
                        ->get('content');
                }
            } catch (ElementNotFoundException $e) {
                //pass
            }
        }

        if (!$languages instanceof AttributeInterface) {
            return $attributes;
        }

        $languages = $this->parseAttribute($languages);

        if ($languages->size() === 0) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), $languages)
        );
    }

    public static function key(): string
    {
        return 'languages';
    }

    private function parseAttribute(AttributeInterface $languages): Set
    {
        $set = new Set('string');
        $languages = explode(',', $languages->value());

        foreach ($languages as $language) {
            $language = (new Str($language))->trim();

            if ($language->matches('~^[a-zA-Z0-9]+(-[a-zA-Z0-9]+)*$~')) {
                $set = $set->add((string) $language);
            }
        }

        return $set;
    }
}
