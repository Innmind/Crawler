<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Visitor;

use Innmind\Url\Url;
use Innmind\Immutable\{
    Set,
    Map,
};
use function Innmind\Immutable\assertSet;

final class RemoveDuplicatedUrls
{
    /**
     * @param Set<Url> $urls
     *
     * @return Set<Url>
     */
    public function __invoke(Set $urls): Set
    {
        assertSet(Url::class, $urls, 1);

        /** @var Set<Url> */
        return $urls
            ->reduce(
                Map::of('string', Url::class),
                static function(Map $urls, Url $url): Map {
                    $string = $url->toString();

                    if ($urls->contains($string)) {
                        return $urls;
                    }

                    return $urls->put($string, $url);
                }
            )
            ->reduce(
                Set::of(Url::class),
                static function(Set $urls, string $string, Url $url): Set {
                    return $urls->add($url);
                }
            );
    }
}
