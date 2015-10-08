<?php

namespace Innmind\Crawler;

use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\ResponseInterface;

class Parser implements ParserInterface
{
    protected $passes = [];

    /**
     * Add a new parsing pass
     *
     * @param ParserInterface $pass
     *
     * @return Parser self
     */
    public function addPass(ParserInterface $pass)
    {
        $this->passes[] = $pass;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(
        Resource $resource,
        ResponseInterface $response,
        Stopwatch $stopwatch
    ) {
        foreach ($this->passes as $parser) {
            $stopwatch->start($parser::getName());
            $parser->parse($resource, $response, $stopwatch);
            $stopwatch->stop($parser::getName());
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'parser';
    }
}
