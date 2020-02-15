<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Crawler\Parser\Html\BaseParser;
use Innmind\UrlResolver\Resolver;
use Innmind\Http\Message\Request;
use Innmind\Url\Url;
use Innmind\Immutable\Map;

final class UrlResolver
{
    private Resolver $resolve;

    public function __construct(Resolver $resolve)
    {
        $this->resolve = $resolve;
    }

    /**
     * @param  Map<string, HttpResource\Attribute> $attributes
     */
    public function __invoke(
        Request $request,
        Map $attributes,
        Url $target
    ): Url {
        $base = $request->url();

        $base = ($this->resolve)(
            $base,
            BaseParser::find($attributes, $base),
        );

        return ($this->resolve)($base, $target);
    }
}
