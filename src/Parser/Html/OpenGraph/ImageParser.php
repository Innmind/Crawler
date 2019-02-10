<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Crawler\Exception\InvalidOpenGraphAttribute;
use Innmind\Xml\Reader;
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class ImageParser extends AbstractPropertyParser
{
    public function __construct(Reader $reader)
    {
        parent::__construct($reader, 'image');
    }

    protected function parseValues(SetInterface $values)
    {
        $urls = $values
            ->filter(static function(string $url): bool {
                return !empty($url);
            })
            ->reduce(
                new Set(UrlInterface::class),
                function(Set $urls, string $url): Set {
                    return $urls->add(Url::fromString($url));
                }
            );

        if ($urls->size() === 0) {
            throw new InvalidOpenGraphAttribute;
        }

        return $urls;
    }

    public static function key(): string
    {
        return 'preview';
    }
}
