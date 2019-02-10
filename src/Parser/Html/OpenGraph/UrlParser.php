<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Parser\Html\OpenGraph;

use Innmind\Xml\Reader;
use Innmind\Url\Url;
use Innmind\Immutable\SetInterface;

final class UrlParser extends AbstractPropertyParser
{
    public function __construct(Reader $reader)
    {
        parent::__construct($reader, 'url');
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
