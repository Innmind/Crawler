<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\{
    ParserInterface,
    Parser\Http\AlternatesParser as HttpParser,
    Parser\Html\AlternatesParser as HtmlParser
};
use Innmind\http\Message\{
    Request,
    Response
};
use Innmind\Immutable\MapInterface;

final class AlternatesParser implements ParserInterface
{
    private $http;
    private $html;

    public function __construct(HttpParser $http, HtmlParser $html)
    {
        $this->http = $http;
        $this->html = $html;
    }

    public function parse(
        Request $request,
        Response $response,
        MapInterface $attributes
    ): MapInterface {
        return $this->merge(
            $this->http->parse($request, $response, $attributes),
            $this->html->parse($request, $response, $attributes)
        );
    }

    public static function key(): string
    {
        return 'alternates';
    }

    private function merge(MapInterface $http, MapInterface $html): MapInterface
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
