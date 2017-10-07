<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Crawler\{
    HttpResource\Attribute,
    Exception\InvalidArgumentException
};
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\{
    File,
    MediaType,
    Name
};
use Innmind\Stream\Readable;
use Innmind\Immutable\MapInterface;

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
        if (
            (string) $attributes->keyType() !== 'string' ||
            (string) $attributes->valueType() !== Attribute::class
        ) {
            throw new InvalidArgumentException;
        }

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
