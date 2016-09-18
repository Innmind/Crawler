<?php
declare(strict_types = 1);

namespace Innmind\Crawler\Transport;

use Innmind\Crawler\{
    Request,
    TransportInterface
};
use Innmind\Http\{
    Message\ResponseInterface,
    Translator\Response\Psr7Translator
};
use GuzzleHttp\ClientInterface;

final class Guzzle implements TransportInterface
{
    private $client;
    private $translator;

    public function __construct(
        ClientInterface $client,
        Psr7Translator $translator
    ) {
        $this->client = $client;
        $this->translator = $translator;
    }

    public function apply(Request $request): ResponseInterface
    {
        $options = [];

        if ($request->hasHeaders()) {
            $options['headers'] = array_combine(
                $request->headers()->keys()->toPrimitive(),
                $request->headers()->values()->toPrimitive()
            );
        }

        if ($request->hasPayload()) {
            $options['body'] = (string) $request->payload();
        }

        $response = $this->client->request(
            (string) $request->method(),
            (string) $request->url(),
            $options
        );

        return $this->translator->translate($response);
    }
}
