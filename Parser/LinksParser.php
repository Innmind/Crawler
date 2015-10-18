<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\Resource;
use Innmind\Crawler\DomCrawlerFactory;
use Innmind\UrlResolver\ResolverInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Message\ResponseInterface;

class LinksParser implements ParserInterface
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
        $links = $dom
            ->filter('
                a[href],
                link[rel="first"][href],
                link[rel="next"][href],
                link[rel="previous"][href],
                link[rel="last"][href]
            ')
            ->reduce(function(Crawler $node) {
                return substr($node->attr('href'), 0, 1) !== '#';
            })
            ->each(function(Crawler $node) use ($resource) {
                return $this->resolver->resolve(
                    $resource->has('base') ? $resource->get('base') : $resource->getUrl(),
                    $node->attr('href')
                );
            });

        if (!empty($links)) {
            $resource->set('links', array_unique($links));
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'links';
    }
}
