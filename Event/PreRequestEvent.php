<?php

namespace Innmind\Crawler\Event;

use Symfony\Component\EventDispatcher\Event;
use GuzzleHttp\Message\RequestInterface;

class PreRequestEvent extends Event
{
    protected $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Return the http request to be sent to retrieve the resource
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }
}
