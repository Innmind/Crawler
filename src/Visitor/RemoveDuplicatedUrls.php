<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Visitor;

use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    SetInterface,
    Set,
    MapInterface,
    Map,
};
use function Innmind\Immutable\assertSet;

final class RemoveDuplicatedUrls
{
    /**
     * @param SetInterface<UrlInterface> $urls
     *
     * @return SetInterface<UrlInterface>
     */
    public function __invoke(SetInterface $urls): SetInterface
    {
        assertSet(UrlInterface::class, $urls, 1);

        return $urls
            ->reduce(
                new Map('string', UrlInterface::class),
                function(MapInterface $urls, UrlInterface $url): MapInterface {
                    $string = (string) $url;

                    if ($urls->contains($string)) {
                        return $urls;
                    }

                    return $urls->put($string, $url);
                }
            )
            ->reduce(
                new Set(UrlInterface::class),
                function(SetInterface $urls, string $string, UrlInterface $url): SetInterface {
                    return $urls->add($url);
                }
            );
    }
}
