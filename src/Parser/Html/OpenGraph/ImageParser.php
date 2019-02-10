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
    Str,
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
                return !Str::of($url)->empty();
            })
            ->reduce(
                new Set(UrlInterface::class),
                function(SetInterface $urls, string $url): SetInterface {
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
