<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Xml\ReaderInterface;
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Immutable\SetInterface;

final class TypeParser extends AbstractPropertyParser
{
    public function __construct(
        ReaderInterface $reader,
        TimeContinuumInterface $clock
    ) {
        parent::__construct($reader, $clock, self::key());
    }

    protected function parseValues(SetInterface $values)
    {
        return $values->current();
    }

    public static function key(): string
    {
        return 'type';
    }
}
