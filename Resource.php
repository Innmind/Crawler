<?php

namespace Innmind\Crawler;

class Resource
{
    protected $url;
    protected $contentType;
    protected $data = [];

    public function __construct($url, $contentType)
    {
        $this->url = (string) $url;
        $this->contentType = (string) $contentType;
    }

    /**
     * Return the resource url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Return the resource content type (the Content-Type header)
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Set an arbitrary key/value pair to this resource
     *
     * @param string $key
     * @param mixed $value
     *
     * @return Resource self
     */
    public function set($key, $value)
    {
        $this->data[(string) $key] = $value;

        return $this;
    }

    /**
     * Check if the given key exist for the resource
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists((string) $key, $this->data);
    }

    /**
     * Return all the keys defined for this resource
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->data);
    }

    /**
     * Return the value for the given key
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown attribute "%s" for resource at "%s"',
                $key,
                $this->url
            ));
        }

        return $this->data[(string) $key];
    }
}
