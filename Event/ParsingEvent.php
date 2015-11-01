<?php

namespace Innmind\Crawler\Event;

use Innmind\Crawler\HttpResource;
use Symfony\Component\EventDispatcher\Event;
use GuzzleHttp\Message\ResponseInterface;

class ParsingEvent extends Event
{
    protected $resource;
    protected $response;

    public function __construct(
        HttpResource $resource,
        ResponseInterface $response
    ) {
        $this->resource = $resource;
        $this->response = $response;
    }

    /**
     * Return the resource containing the parsed data
     *
     * @return HttResource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Return the http response
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}
