<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\Resource;
use Innmind\Crawler\DomCrawlerFactory;
use Innmind\UrlResolver\ResolverInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\ResponseInterface;

class CanonicalParser implements ParserInterface
{
    protected $resolver;
    protected $crawlerFactory;

    public function __construct(
        ResolverInterface $resolver,
        DomCrawlerFactory $crawlerFactory
    ) {
        $this->resolver = $resolver;
        $this->crawlerFactory = $crawlerFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(
        Resource $resource,
        ResponseInterface $response,
        Stopwatch $stopwatch
    ) {
        if (!preg_match('/html/', $resource->getContentType())) {
            return $resource;
        }

        $dom = $this->crawlerFactory->make($response);
        $canonical = $dom->filter('link[rel="canonical"][href]');

        if ($canonical->count() === 1) {
            $resource->set(
                'canonical',
                $this->resolver->resolve(
                    $resource->getUrl(),
                    $canonical->attr('href')
                )
            );
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'canonical';
    }
}
