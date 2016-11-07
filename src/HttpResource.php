<?php
declare(strict_types = 1);

namespace Innmind\Crawler;

use Innmind\Crawler\{
    HttpResource\AttributeInterface,
    Exception\InvalidArgumentException
};
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\{
    FileInterface,
    MediaTypeInterface,
    StreamInterface,
    Name,
    NameInterface
};
use Innmind\Immutable\MapInterface;

final class HttpResource implements FileInterface
{
    private $url;
    private $name;
    private $mediaType;
    private $attributes;
    private $content;

    public function __construct(
        UrlInterface $url,
        MediaTypeInterface $mediaType,
        MapInterface $attributes,
        StreamInterface $content
    ) {
        if (
            (string) $attributes->keyType() !== 'string' ||
            (string) $attributes->valueType() !== AttributeInterface::class
        ) {
            throw new InvalidArgumentException;
        }

        $name = basename((string) $url->path());
        $this->url = $url;
        $this->name = new Name(empty($name) ? 'index' : $name);
        $this->mediaType = $mediaType;
        $this->attributes = $attributes;
        $this->content = $content;
    }

    public function url(): UrlInterface
    {
        return $this->url;
    }

    public function name(): NameInterface
    {
        return $this->name;
    }

    public function mediaType(): MediaTypeInterface
    {
        return $this->mediaType;
    }

    public function content(): StreamInterface
    {
        return $this->content;
    }

    /**
     * @return MapInterface<string, AttributeInterface>
     */
    public function attributes(): MapInterface
    {
        return $this->attributes;
    }
}
