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

final class IosParser implements Parser
{
    private const PATTERN = '/app\-argument\="?(?P<uri>.*)"?$/';

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

        $meta = $metas->filter(static function(Element $meta): bool {
            return $meta->attributes()->contains('name') &&
                $meta->attributes()->get('name')->value() === 'apple-itunes-app' &&
                $meta->attributes()->contains('content');
        });

        if ($meta->size() !== 1) {
            return $attributes;
        }

        $content = first($meta)->attributes()->get('content')->value();
        $content = Str::of($content);

        if (!$content->matches(self::PATTERN)) {
            return $attributes;
        }

        $matches = $content->capture(self::PATTERN);

        return $attributes->put(
            self::key(),
            new Attribute(self::key(), $matches->get('uri')->toString())
        );
    }

    public static function key(): string
    {
        return 'ios';
    }
}
