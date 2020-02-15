<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Crawler\HttpResource\Attribute;
use Innmind\Url\Url;
use Innmind\Filesystem\{
    File,
    Name,
};
use Innmind\MediaType\MediaType;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Map,
    Str,
};
use function Innmind\Immutable\assertMap;

final class HttpResource implements File
{
    private Url $url;
    private Name $name;
    private MediaType $mediaType;
    /** @var Map<string, Attribute> */
    private Map $attributes;
    private Readable $content;

    /**
     * @param Map<string, Attribute> $attributes
     */
    public function __construct(
        Url $url,
        MediaType $mediaType,
        Map $attributes,
        Readable $content
    ) {
        assertMap('string', Attribute::class, $attributes, 3);

        $name = \basename($url->path()->toString());
        $this->url = $url;
        $this->name = new Name(Str::of($name)->empty() ? 'index' : $name);
        $this->mediaType = $mediaType;
        $this->attributes = $attributes;
        $this->content = $content;
    }

    public function url(): Url
    {
        return $this->url;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function mediaType(): MediaType
    {
        return $this->mediaType;
    }

    public function content(): Readable
    {
        return $this->content;
    }

    /**
     * @return Map<string, Attribute>
     */
    public function attributes(): Map
    {
        return $this->attributes;
    }
}
