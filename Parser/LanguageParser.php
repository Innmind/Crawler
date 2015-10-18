<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\Resource;
use Innmind\Crawler\DomCrawlerFactory;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\ResponseInterface;

class LanguageParser implements ParserInterface
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
        Resource $resource,
        ResponseInterface $response,
        Stopwatch $stopwatch
    ) {
        if ($response->hasHeader('Content-Language')) {
            $this->parseLanguages(
                $response->getHeader('Content-Language'),
                $resource
            );
        }

        if (!preg_match('/html/', $resource->getContentType())) {
            return $resource;
        }

        $dom = $this->crawlerFactory->make($response);
        $html = $dom->filter('html');

        if ($html->count() === 1) {
            $this->parseLanguages($html->attr('lang'), $resource);
        }

        $meta = $dom->filter('meta[http-equiv="Content-Language"][content]');

        if ($meta->count() === 1) {
            $this->parseLanguages($meta->attr('content'), $resource);
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'languages';
    }

    /**
     * Parse the languages string to extract all set values
     *
     * If the language is already set in the resource, no averride is done
     *
     * @param string $languages
     * @param Resource $resource
     *
     * @return void
     */
    protected function parseLanguages($languages, Resource $resource)
    {
        if ($resource->has('languages')) {
            return;
        }

        $languages = explode(',', (string) $languages);
        $found = [];

        foreach ($languages as $language) {
            $language = trim($language);

            if (preg_match('/^[a-zA-Z]+$/', $language)) {
                $found[] = strtolower($language);
            }
        }

        if (!empty($found)) {
            $resource->set('languages', $found);
        }
    }
}
