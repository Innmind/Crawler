<?php

namespace Innmind\Crawler;

interface CrawlerInterface
{
    /**
     * Retrieve the resource content specified by the request url
     *
     * Incase the resource type is supported, the library will extract various
     * information out of it
     *
     * @param Request $request
     *
     * @return Resource
     */
    public function crawl(Request $request);

    /**
     * Return a stopwatch containing the times spent for each part of the crawl
     *
     * @param Resource $resource
     *
     * @return Stopwatch
     */
    public function getStopwatch(Resource $resource);

    /**
     * Clear inner references to the given resource
     *
     * @param Resource $resource
     *
     * @return CrawlerInterface self
     */
    public function release(Resource $resource);
}
