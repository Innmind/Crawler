<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Visitor;

use Innmind\Crawler\Exception\InvalidArgumentException;
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    SetInterface,
    Set,
    Map
};

final class RemoveDuplicatedUrls
{
    /**
     * @param SetInterface<UrlInterface> $urls
     *
     * @return SetInterface<UrlInterface>
     */
    public function __invoke(SetInterface $urls): SetInterface
    {
        if ((string) $urls->type() !== UrlInterface::class) {
            throw new InvalidArgumentException;
        }

        return $urls
            ->reduce(
                new Map('string', UrlInterface::class),
                function(Map $urls, UrlInterface $url): Map {
                    $string = (string) $url;

                    if ($urls->contains($string)) {
                        return $urls;
                    }

                    return $urls->put($string, $url);
                }
            )
            ->reduce(
                new Set(UrlInterface::class),
                function(Set $urls, string $string, UrlInterface $url): Set {
                    return $urls->add($url);
                }
            );
    }
}
