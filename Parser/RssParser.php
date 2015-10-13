<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\Resource;
use Innmind\UrlResolver\ResolverInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Message\ResponseInterface;

class RssParser implements ParserInterface
{
    protected $resolver;

    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
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

        $dom = new Crawler((string) $response->getBody());
        $rss = $dom->filter('link[rel="alternate"][type="application/rss+xml"][href]');

        if ($rss->count() === 1) {
            $resource->set(
                'rss',
                $this->resolver->resolve(
                    $resource->has('base') ? $resource->get('base') : $resource->getUrl(),
                    $rss->attr('href')
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
        return 'rss';
    }
}
