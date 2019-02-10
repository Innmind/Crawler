<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Crawler\HttpResource\Attribute;
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\{
    File,
    MediaType,
    Name,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\MapInterface;
use function Innmind\Immutable\assertMap;

final class HttpResource implements File
{
    private $url;
    private $name;
    private $mediaType;
    private $attributes;
    private $content;

    public function __construct(
        UrlInterface $url,
        MediaType $mediaType,
        MapInterface $attributes,
        Readable $content
    ) {
        assertMap('string', Attribute::class, $attributes, 3);

        $name = basename((string) $url->path());
        $this->url = $url;
        $this->name = new Name\Name(empty($name) ? 'index' : $name);
        $this->mediaType = $mediaType;
        $this->attributes = $attributes;
        $this->content = $content;
    }

    public function url(): UrlInterface
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
     * @return MapInterface<string, Attribute>
     */
    public function attributes(): MapInterface
    {
        return $this->attributes;
    }
}
