<?php

namespace Innmind\Crawler;

use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\ResponseInterface;

interface ParserInterface
{
    /**
     * Extract informations out of the response and inject it in the response
     *
     * It may also add a section to the stopwatch
     *
     * @param Resource $resource
     * @param ResponseInterface $response
     * @param Stopwatch $stopwatch
     *
     * @return Resource
     */
    public function parse(
        Resource $resource,
        ResponseInterface $response,
        Stopwatch $stopwatch
    );

    /**
     * Return the name of the pass
     *
     * @return string
     */
    public static function getName();
}
