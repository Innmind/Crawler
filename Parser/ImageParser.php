<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\Resource;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\ResponseInterface;

/**
 * Extract info out of an image
 */
class ImageParser implements ParserInterface
{
    /**
     * {@inheritdoc}
     */
    public function parse(
        Resource $resource,
        ResponseInterface $response,
        Stopwatch $stopwatch
    ) {
        if (!preg_match('/image\/.*/', $resource->getContentType())) {
            return $resource;
        }

        $stopwatch->start('image_size');
        $size = getimagesize($resource->getUrl());
        $resource
            ->set('width', $size[0])
            ->set('height', $size[1])
            ->set('mime', image_type_to_mime_type($size[2]))
            ->set('extension', image_type_to_extension($size[2]));
        $stopwatch->stop('image_size');

        $stopwatch->start('exif');
        $this->readExif($resource);
        $stopwatch->stop('exif');
        $this->findWeight($resource, $response);

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'image';
    }

    /**
     * Try to find the image weight
     *
     * @param Resource $resource
     * @param ResponseInterface $response
     *
     * @return void
     */
    protected function findWeight(
        Resource $resource,
        ResponseInterface $response
    ) {
        if ($response->hasHeader('Content-Length')) {
            $resource->set(
                'weight',
                (int) $response->getHeader('Content-Length')
            );
        } else {
            if ($resource->has('exif')) {
                $exif = $resource->get('exif');

                if (isset($exif['FILE.FileSize'])) {
                    $resource->set('weight', (int) $exif['FILE.FileSize']);

                    return;
                } else if (isset($exif['FileSize'])) {
                    $resource->set('weight', (int) $exif['FileSize']);

                    return;
                }
            }

            $body = $response->getBody();

            if ($body !== null) {
                $resource->set('weight', (int) $body->getSize());
            }
        }
    }

    /**
     * Read all the exif data from the image
     *
     * @param Resource $resource
     *
     * @return void
     */
    protected function readExif(Resource $resource)
    {
        if (!preg_match('/image\/jpeg/', $resource->getContentType())) {
            return;
        }

        $exif = exif_read_data($resource->getUrl());

        if ($exif !== false) {
            $data = [];

            foreach ($exif as $key => $section) {
                if (is_array($section)) {
                    foreach ($section as $name => $value) {
                        $data[$key . '.' . $name] = $value;
                    }
                } else {
                    $data[$key] = $section;
                }
            }

            $resource->set('exif', $data);
        }
    }
}
