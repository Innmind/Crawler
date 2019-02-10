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
use Innmind\Immutable\MapInterface;

final class HtmlParser implements Parser
{
    private $parse;
    private $html;

    public function __construct(Parser $parse)
    {
        $this->parse = $parse;
        $this->html = new Html;
    }

    public function __invoke(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        if (!$attributes->contains(ContentTypeParser::key())) {
            return $attributes;
        }

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
