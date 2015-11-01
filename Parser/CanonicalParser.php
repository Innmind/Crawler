<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\HttpResource;
use Innmind\Crawler\DomCrawlerFactory;
use Innmind\UrlResolver\ResolverInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Message\Response;

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
        HttpResource $resource,
        ResponseInterface $response,
        Stopwatch $stopwatch
    ) {
        $link = $this->getLinkFromHeader($response);

        if (
            empty($link) &&
            preg_match('/html/', $resource->getContentType())
        ) {
            $link = $this->getLinkFromHtml($response);
        }

        if (!empty($link)) {
            $resource->set(
                'canonical',
                $this->resolver->resolve(
                    $resource->getUrl(),
                    $link
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

    /**
     * Extract canonical link from response headers
     *
     * @param ResponseInterface $response
     *
     * @return string
     */
    protected function getLinkFromHeader(ResponseInterface $response)
    {
        $links = Response::parseHeader($response, 'Link');

        foreach ($links as $link) {
            if (isset($link['rel']) && $link['rel'] === 'canonical') {
                return substr($link[0], 1, -1);
            }
        }
    }

    /**
     * Extract canonical link from html link tag
     *
     * @param ResponseInterface $response
     *
     * @return string
     */
    protected function getLinkFromHtml(ResponseInterface $response)
    {
        $dom = $this->crawlerFactory->make($response);
        $canonical = $dom->filter('link[rel="canonical"][href]');

        if ($canonical->count() === 1) {
            return $canonical->attr('href');
        }
    }
}
