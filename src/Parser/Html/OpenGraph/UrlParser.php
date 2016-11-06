<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Xml\ReaderInterface;
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Url\Url;
use Innmind\Immutable\SetInterface;

final class UrlParser extends AbstractPropertyParser
{
    public function __construct(
        ReaderInterface $reader,
        TimeContinuumInterface $clock
    ) {
        parent::__construct($reader, $clock, 'url');
    }

    protected function parseValues(SetInterface $values)
    {
        return Url::fromString($values->current());
    }

    public static function key(): string
    {
        return 'canonical';
    }
}
