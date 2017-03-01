<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Xml\ReaderInterface;
use Innmind\Immutable\SetInterface;

final class TypeParser extends AbstractPropertyParser
{
    public function __construct(ReaderInterface $reader)
    {
        parent::__construct($reader, self::key());
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
