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

final class IosParser implements Parser
{
    use HtmlTrait;

    private const PATTERN = '/app\-argument\="?(?P<uri>.*)"?$/';

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

        $meta = $metas->filter(static function(Element $meta): bool {
            return $meta->attributes()->contains('name') &&
                $meta->attributes()->get('name')->value() === 'apple-itunes-app' &&
                $meta->attributes()->contains('content');
        });

        if ($meta->size() !== 1) {
            return $attributes;
        }

        $content = $meta->current()->attributes()->get('content')->value();
        $content = Str::of($content);

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
