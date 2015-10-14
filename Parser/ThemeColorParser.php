<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\Resource;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Message\ResponseInterface;

class ThemeColorParser implements ParserInterface
{
    const SHORT_HEX_PATTERN = '/^#[0-9a-fA-F]{3}$/';
    const LONG_HEX_PATTERN = '/^#[0-9a-fA-F]{6}$/';
    const RGB_PATTERN = '/^rgb\((?P<red>\d{1,3}), ?(?P<green>\d{1,3}), ?(?P<blue>\d{1,3})\)$/';
    const HSL_PATTERN = '/^hsl\((?P<hue>\d{1,3}\.?\d?), ?(?P<sat>\d{1,3}\.?\d?)%, ?(?P<lit>\d{1,3}\.?\d?)%\)$/';

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
        $color = $dom->filter('meta[name="theme-color"][content]');

        if ($color->count() === 1) {
            $color = $color->attr('content');

            switch (true) {
                case (bool) preg_match(self::SHORT_HEX_PATTERN, $color):
                    $parts = str_split(substr($color, 1));
                    foreach ($parts as &$part) {
                        $part = hexdec($part.$part);
                    }
                    list($red, $green, $blue) = $parts;
                    break;
                case (bool) preg_match(self::LONG_HEX_PATTERN, $color):
                    $parts = str_split(substr($color, 1), 2);
                    foreach ($parts as &$part) {
                        $part = hexdec($part);
                    }
                    list($red, $green, $blue) = $parts;
                    break;
                case (bool) preg_match(self::RGB_PATTERN, $color, $rgb):
                    $red = $rgb['red'];
                    $green = $rgb['green'];
                    $blue = $rgb['blue'];
                    break;
                case (bool) preg_match(self::HSL_PATTERN, $color, $hsl):
                    $hue = round((float) $hsl['hue'], 1);
                    $sat = round((float) $hsl['sat'], 1);
                    $lit = round((float) $hsl['lit'], 1);
                    break;
                default:
                    return $resource;
            }

            if (isset($red)) {
                list($hue, $sat, $lit) = $this->convertRGBToHsl(
                    $red,
                    $green,
                    $blue
                );
            }

            $resource->set('theme-color', [$hue, $sat, $lit]);
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'theme-color';
    }

    /**
     * Convert a rgb code to a valid hsl components
     *
     * @param int $red
     * @param int $green
     * @param int $blue
     *
     * @return array
     */
    protected function convertRGBToHsl($red, $green, $blue)
    {
        $red = (int) $red;
        $green = (int) $green;
        $blue = (int) $blue;
        $red /= 255;
        $green /= 255;
        $blue /= 255;

        $max = max($red, $green, $blue);
        $min = min($red, $green, $blue);
        $lit = ($max + $min) / 2;

        if ($max === $min) {
            $hue = $sat = 0;
        } else {
            $diff = $max - $min;
            $sat = $lit > 0.5 ?
                $diff / (2 - $max - $min) :
                $diff / ($max + $min);

            switch ($max) {
                case $red:
                    $hue = (($green - $blue) / $diff) + ($green < $blue ? 6 : 0);
                    break;

                case $green:
                    $hue = (($blue - $red) / $diff) + 2;
                    break;

                case $blue:
                    $hue = (($red - $green) / $diff) + 4;
                    break;
            }
        }

        $hue *= 60;

        return [
            $hue ? round($hue, 1) : 0,
            $sat ? round($sat * 100, 1) : 0,
            $lit ? round($lit * 100, 1) : 0,
        ];
    }
}
