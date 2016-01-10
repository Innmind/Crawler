<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\HttpResource;
use Innmind\Crawler\DomCrawlerFactory;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\ResponseInterface;

class DescriptionParser implements ParserInterface
{
    protected $crawlerFactory;

    public function __construct(DomCrawlerFactory $crawlerFactory)
    {
        $this->crawlerFactory = $crawlerFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(
        HttpResource $resource,
        ResponseInterface $response,
        Stopwatch $stopwatch
    ) {
        if (!preg_match('/html/', $resource->getContentType())) {
            return $resource;
        }

        $dom = $this->crawlerFactory->make($response);
        $description = $dom->filter('meta[name="description"][content]');

        if ($description->count() === 1) {
            $desc = $description->attr('content');
        } else if ($resource->has('content')) {
            $content = preg_replace('/\t/m', ' ', $resource->get('content'));
            $content = preg_replace('/ {2,}/m', ' ', $content);
            $desc = substr($content, 0, 150);
            $desc .= '...';
        }

        if (isset($desc)) {
            $resource->set('description', $desc);
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'description';
    }
}
