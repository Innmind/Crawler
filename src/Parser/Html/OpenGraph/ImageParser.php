<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Xml\ReaderInterface;
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Url\{
    Url,
    UrlInterface
};
use Innmind\Immutable\{
    SetInterface,
    Set
};

final class ImageParser extends AbstractPropertyParser
{
    public function __construct(
        ReaderInterface $reader,
        TimeContinuumInterface $clock
    ) {
        parent::__construct($reader, $clock, 'image');
    }

    protected function parseValues(SetInterface $values)
    {
        return $values->reduce(
            new Set(UrlInterface::class),
            function(Set $urls, string $url): Set {
                return $urls->add(Url::fromString($url));
            }
        );
    }

    public static function key(): string
    {
        return 'preview';
    }
}