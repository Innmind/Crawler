<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Crawler\{
    Parser,
    HttpResource\Attribute\Attribute,
    Parser\Html\HtmlTrait,
    Visitor\Html\OpenGraph,
};
use Innmind\Xml\Reader;
use Innmind\Url\Url;
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\MapInterface;

final class UrlParser implements Parser
{
    use HtmlTrait;

    private $read;
    private $extract;

    public function __construct(Reader $read)
    {
        $this->read = $read;
        $this->extract = new OpenGraph('url');
    }

    public function __invoke(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        if (!$this->isHtml($attributes)) {
            return $attributes;
        }

        $document = ($this->read)($response->body());

        $values = ($this->extract)($document);

        if ($values->empty()) {
            return $attributes;
        }

        return $attributes->put(
            self::key(),
            new Attribute(
                self::key(),
                Url::fromString($values->current())
            )
        );
    }

    public static function key(): string
    {
        return 'canonical';
    }
}
