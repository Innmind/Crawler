<?php

namespace Innmind\Crawler\Event;

use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;

class PostRequestEvent extends PreRequestEvent
{
    protected $response;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response
    ) {
        parent::__construct($request);
        $this->response = $response;
    }

    /**
     * Return the http response retrieved
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}
