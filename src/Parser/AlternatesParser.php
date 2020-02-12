<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\{
    Parser,
    Parser\Http\AlternatesParser as HttpParser,
    Parser\Html\AlternatesParser as HtmlParser,
};
use Innmind\http\Message\{
    Request,
    Response,
};
use Innmind\Immutable\Map;

final class AlternatesParser implements Parser
{
    private Parser $http;
    private Parser $html;

    public function __construct(Parser $http, Parser $html)
    {
        $this->http = $http;
        $this->html = $html;
    }

    public function __invoke(
        Request $request,
        Response $response,
        Map $attributes
    ): Map {
        return $this->merge(
            ($this->http)($request, $response, $attributes),
            ($this->html)($request, $response, $attributes)
        );
    }

    public static function key(): string
    {
        return 'alternates';
    }

    private function merge(Map $http, Map $html): Map
    {
        if (!$http->contains(HttpParser::key())) {
            return $html;
        }

        if (!$html->contains(HtmlParser::key())) {
            return $http;
        }

        return $http->put(
            self::key(),
            $http
                ->get(HttpParser::key())
                ->merge(
                    $html->get(HtmlParser::key())
                )
        );
    }
}
