<?php

namespace Innmind\Crawler;

use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use GuzzleHttp\Message\ResponseInterface;

class DomCrawlerFactory
{
    protected $crawlers;

    public function __construct()
    {
        $this->crawlers = new \SplObjectStorage;
    }

    /**
     * Create a dom crawler for the given response
     *
     * @param ResponseInterface $response
     *
     * @return DomCrawler
     */
    public function make(ResponseInterface $response)
    {
        if ($this->crawlers->contains($response)) {
            return $this->crawlers[$response];
        }

        if (
            !$response->hasHeader('Content-Type') ||
            !preg_match('/html/', $response->getHeader('Content-Type'))
        ) {
            throw new \InvalidArgumentException(
                'A DomCrawler can only be created for html content'
            );
        }

        $crawler = new DomCrawler((string) $response->getBody());
        $this->crawlers->attach($response, $crawler);

        return $crawler;
    }

    /**
     * Remove any references to the given response
     *
     * So the attached crawler can be garbage collected
     *
     * @param ResponseInterface $response
     *
     * @return DomCrawlerFactory self
     */
    public function release(ResponseInterface $response)
    {
        $this->crawlers->detach($response);

        return $this;
    }
}
