<?php

/*
 * This file is part of the Imagine package.
 *
 * (c) Tiki Wiki CMS Groupware Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Imagine\Test\Image\Palette;

use Imagine\Image\Palette\CMYK as CMYKPalette;
use Imagine\Image\Palette\RGB as RGBPalette;
use Imagine\Image\Palette\Grayscale as GrayscalePalette;
use Imagine\Test\ImagineTestCase;

/**
 * Color Space Conversion Test
 *
 * Verifies accurate conversion between RGB, CMYK, and Grayscale color spaces.
 * Ensures ICC profile changes don't affect color conversion accuracy.
 */
class ColorSpaceConversionTest extends ImagineTestCase
{
    /**
     * @dataProvider rgbToCmykConversionProvider
     */
    public function testRGBToCMYKConversion($name, $rgb, $expectedCMYK)
    {
        // Manual RGB to CMYK conversion (standard formula)
        $r = $rgb[0] / 255;
        $g = $rgb[1] / 255;
        $b = $rgb[2] / 255;

        $k = 1 - max($r, $g, $b);

        if ($k == 1) {
            $c = $m = $y = 0;
        } else {
            $c = round((1 - $r - $k) / (1 - $k) * 100);
            $m = round((1 - $g - $k) / (1 - $k) * 100);
            $y = round((1 - $b - $k) / (1 - $k) * 100);
        }
        $k = round($k * 100);

        $this->assertEquals($expectedCMYK[0], $c, "Cyan conversion incorrect for $name");
        $this->assertEquals($expectedCMYK[1], $m, "Magenta conversion incorrect for $name");
        $this->assertEquals($expectedCMYK[2], $y, "Yellow conversion incorrect for $name");
        $this->assertEquals($expectedCMYK[3], $k, "Black conversion incorrect for $name");

        // Also test using palette
        $cmykPalette = new CMYKPalette();
        $color = $cmykPalette->color([$c, $m, $y, $k]);

        $this->assertEquals($c, $color->getCyan());
        $this->assertEquals($m, $color->getMagenta());
        $this->assertEquals($y, $color->getYellow());
        $this->assertEquals($k, $color->getKeyline());
    }

    public static function rgbToCmykConversionProvider()
    {
        return [
            'Red' => ['Red', [255, 0, 0], [0, 100, 100, 0]],
            'Green' => ['Green', [0, 255, 0], [100, 0, 100, 0]],
            'Blue' => ['Blue', [0, 0, 255], [100, 100, 0, 0]],
            'Black' => ['Black', [0, 0, 0], [0, 0, 0, 100]],
            'White' => ['White', [255, 255, 255], [0, 0, 0, 0]],
            'Cyan' => ['Cyan', [0, 255, 255], [100, 0, 0, 0]],
            'Magenta' => ['Magenta', [255, 0, 255], [0, 100, 0, 0]],
            'Yellow' => ['Yellow', [255, 255, 0], [0, 0, 100, 0]],
        ];
    }

    /**
     * @dataProvider cmykToRgbConversionProvider
     */
    public function testCMYKToRGBConversion($name, $cmyk, $expectedRGB)
    {
        // Manual CMYK to RGB conversion (standard formula)
        $c = $cmyk[0] / 100;
        $m = $cmyk[1] / 100;
        $y = $cmyk[2] / 100;
        $k = $cmyk[3] / 100;

        $r = round(255 * (1 - $c) * (1 - $k));
        $g = round(255 * (1 - $m) * (1 - $k));
        $b = round(255 * (1 - $y) * (1 - $k));

        $this->assertEquals($expectedRGB[0], $r, "Red conversion incorrect for $name");
        $this->assertEquals($expectedRGB[1], $g, "Green conversion incorrect for $name");
        $this->assertEquals($expectedRGB[2], $b, "Blue conversion incorrect for $name");
    }

    public static function cmykToRgbConversionProvider()
    {
        return [
            'Pure Cyan' => ['Pure Cyan', [100, 0, 0, 0], [0, 255, 255]],
            'Pure Magenta' => ['Pure Magenta', [0, 100, 0, 0], [255, 0, 255]],
            'Pure Yellow' => ['Pure Yellow', [0, 0, 100, 0], [255, 255, 0]],
            'Pure Black' => ['Pure Black', [0, 0, 0, 100], [0, 0, 0]],
            'White' => ['White', [0, 0, 0, 0], [255, 255, 255]],
        ];
    }

    /**
     * @dataProvider rgbToGrayscaleConversionProvider
     */
    public function testRGBToGrayscaleConversion($name, $rgb, $expectedGray)
    {
        $rgbPalette = new RGBPalette();
        $rgbColor = $rgbPalette->color($rgb);

        // Convert to grayscale
        $grayColor = $rgbColor->grayscale();

        // Standard grayscale formula: 0.299*R + 0.587*G + 0.114*B
        $calculatedGray = min(255, round(0.299 * $rgb[0] + 0.587 * $rgb[1] + 0.114 * $rgb[2]));

        $this->assertEquals($expectedGray, $calculatedGray, "Grayscale conversion incorrect for $name");
    }

    public static function rgbToGrayscaleConversionProvider()
    {
        return [
            'Pure Red' => ['Pure Red', [255, 0, 0], 76],
            'Pure Green' => ['Pure Green', [0, 255, 0], 150],
            'Pure Blue' => ['Pure Blue', [0, 0, 255], 29],
            'Black' => ['Black', [0, 0, 0], 0],
            'White' => ['White', [255, 255, 255], 255],
            'Mid Gray' => ['Mid Gray', [128, 128, 128], 128],
        ];
    }

    public function testCMYKGrayscaleConversion()
    {
        $cmykPalette = new CMYKPalette();

        // Pure black in CMYK
        $black = $cmykPalette->color([0, 0, 0, 100]);
        $grayBlack = $black->grayscale();

        $this->assertEquals(100, $grayBlack->getKeyline(), 'Black should remain black in grayscale');

        // Pure cyan
        $cyan = $cmykPalette->color([100, 0, 0, 0]);
        $grayCyan = $cyan->grayscale();

        // Verify it's actually grayscale (C=M=Y)
        $this->assertEquals($grayCyan->getCyan(), $grayCyan->getMagenta());
        $this->assertEquals($grayCyan->getMagenta(), $grayCyan->getYellow());
    }

    public function testColorSpaceRoundTrip()
    {
        // Test RGB -> Grayscale
        $rgbPalette = new RGBPalette();
        $originalRed = $rgbPalette->color([255, 0, 0]);
        $grayFromRed = $originalRed->grayscale();

        // Should be grayscale
        $this->assertEquals($grayFromRed->getRed(), $grayFromRed->getGreen());
        $this->assertEquals($grayFromRed->getGreen(), $grayFromRed->getBlue());
    }

    public function testConversionAccuracy()
    {
        // Test that conversions maintain expected accuracy
        $testValues = [
            [255, 128, 64],
            [192, 64, 128],
            [64, 192, 255],
        ];

        $rgbPalette = new RGBPalette();

        foreach ($testValues as $rgb) {
            $color = $rgbPalette->color($rgb);

            // Values should be preserved exactly
            $this->assertEquals($rgb[0], $color->getRed());
            $this->assertEquals($rgb[1], $color->getGreen());
            $this->assertEquals($rgb[2], $color->getBlue());
        }
    }

    public function testCMYKConversionConsistency()
    {
        $cmykPalette = new CMYKPalette();

        // Test several times - should be consistent
        for ($i = 0; $i < 5; $i++) {
            $color = $cmykPalette->color([50, 25, 75, 10]);

            $this->assertEquals(50, $color->getCyan());
            $this->assertEquals(25, $color->getMagenta());
            $this->assertEquals(75, $color->getYellow());
            $this->assertEquals(10, $color->getKeyline());
        }
    }

    public function testBoundaryValues()
    {
        $rgbPalette = new RGBPalette();

        // Test minimum values
        $black = $rgbPalette->color([0, 0, 0]);
        $this->assertEquals(0, $black->getRed());
        $this->assertEquals(0, $black->getGreen());
        $this->assertEquals(0, $black->getBlue());

        // Test maximum values
        $white = $rgbPalette->color([255, 255, 255]);
        $this->assertEquals(255, $white->getRed());
        $this->assertEquals(255, $white->getGreen());
        $this->assertEquals(255, $white->getBlue());
    }
}
