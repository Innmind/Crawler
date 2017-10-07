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
use Innmind\Immutable\{
    MapInterface,
    Str
};

final class IosParser implements ParserInterface
{
    use HtmlTrait;

    const PATTERN = '/app\-argument\="?(?P<uri>.*)"?$/';

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
            $metas = (new Elements('meta'))(
                (new Head)($document)
            );
        } catch (ElementNotFoundException $e) {
            return $attributes;
        }

        $meta = $metas->filter(function(ElementInterface $meta): bool {
            return $meta->attributes()->contains('name') &&
                $meta->attributes()->get('name')->value() === 'apple-itunes-app' &&
                $meta->attributes()->contains('content');
        });

        if ($meta->size() !== 1) {
            return $attributes;
        }

        $content = $meta->current()->attributes()->get('content')->value();
        $content = (new Str($content));

        if (!$content->matches(self::PATTERN)) {
            return $attributes;
        }

        $matches = $content->capture(self::PATTERN);

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), (string) $matches->get('uri'))
        );
    }

    public static function key(): string
    {
        return 'ios';
    }
}
