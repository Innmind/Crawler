<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Crawler\Parser\Html\BaseParser;
use Innmind\UrlResolver\ResolverInterface;
use Innmind\Http\Message\Request;
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Immutable\MapInterface;

final class UrlResolver
{
    private ResolverInterface $resolver;

    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @param  MapInterface<string, AttributeInterface> $attributes
     */
    public function __invoke(
        Request $request,
        MapInterface $attributes,
        UrlInterface $target
    ): UrlInterface {
        $base = $request->url();

        if ($attributes->contains(BaseParser::key())) {
            $base = $attributes->get(BaseParser::key())->content();
        }

        return Url::fromString(
            $this->resolver->resolve(
                (string) $base,
                (string) $target
            )
        );
    }
}
