<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\HttpTransport\Transport;
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Xml\Reader;
use Innmind\UrlResolver\ResolverInterface;

function bootstrap(
    Transport $transport,
    TimeContinuumInterface $clock = null,
    Reader $reader = null,
    ResolverInterface $resolver = null,
    Parser $parser = null
): Crawler {
    $parser = $parser ?? new Parser\SequenceParser(
        new Parser\Http\ContentTypeParser,
        new Parser\Http\CacheParser($clock),
        new Parser\Html\BaseParser($reader),
        new Parser\AlternatesParser(
            new Parser\Http\AlternatesParser($resolver = new UrlResolver($resolver)),
            new Parser\Html\AlternatesParser($reader, $resolver)
        ),
        new Parser\ConditionalParser(
            new Parser\Html\OpenGraph\UrlParser($reader),
            new Parser\Html\CanonicalParser($reader, $resolver),
            new Parser\Http\CanonicalParser($resolver)
        ),
        new Parser\ConditionalParser(
            new Parser\Html\CharsetParser($reader),
            new Parser\Http\CharsetParser
        ),
        new Parser\ConditionalParser(
            new Parser\Html\LanguagesParser($reader),
            new Parser\Http\LanguagesParser
        ),
        new Parser\Html\OpenGraph\ImageParser($reader),
        new Parser\Html\OpenGraph\TypeParser($reader),
        new Parser\Html\TitleParser($reader),
        new Parser\Html\AnchorsParser($reader),
        new Parser\Html\AndroidParser($reader),
        new Parser\Html\AuthorParser($reader),
        new Parser\Html\CitationsParser($reader),
        new Parser\Html\ContentParser($reader),
        new Parser\Html\DescriptionParser($reader),
        new Parser\Html\ImagesParser($reader, $resolver),
        new Parser\Html\IosParser($reader),
        new Parser\Html\JournalParser($reader),
        new Parser\Html\LinksParser($reader, $resolver),
        new Parser\Html\RssParser($reader, $resolver),
        new Parser\Html\ThemeColorParser($reader),
        new Parser\Image\DimensionParser,
        new Parser\Image\WeightParser
    );

    return new Crawler\Crawler($transport, $parser);
}
