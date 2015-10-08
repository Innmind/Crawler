<?php

namespace Innmind\Crawler;

class Request
{
    protected $url;
    protected $headers;

    public function __construct($url, array $headers = [])
    {
        $this->url = (string) $url;
        $this->headers = $headers;
    }

    /**
     * Return the url to be crawled
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Return various headers to be used to retrieve the resource
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
