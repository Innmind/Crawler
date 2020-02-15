<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\{
    Parser,
    Parser\Http\ContentTypeParser,
    Specification\Html,
};
use Innmind\Http\Message\{
    Request,
    Response,
};
use Innmind\MediaType\MediaType;
use Innmind\Immutable\Map;

final class HtmlParser implements Parser
{
    private Parser $parse;
    private Html $html;

    public function __construct(Parser $parse)
    {
        $this->parse = $parse;
        $this->html = new Html;
    }

    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        if (!$attributes->contains(ContentTypeParser::key())) {
            return $attributes;
        }

        /** @var MediaType */
        $type = $attributes->get(ContentTypeParser::key())->content();

        if (!$this->html->isSatisfiedBy($type)) {
            return $attributes;
        }

        return ($this->parse)($request, $response, $attributes);
    }

    public static function key(): string
    {
        return 'html';
    }
}
