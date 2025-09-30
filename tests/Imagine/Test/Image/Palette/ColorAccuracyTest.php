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
use Imagine\Test\ImagineTestCase;

/**
 * Color Accuracy Test
 *
 * Verifies that color values are accurately represented across different palettes.
 * This test ensures quality is maintained after replacing Adobe ICC profiles
 * with open-source alternatives (ISOcoated_v2_grey1c_bas.ICC).
 */
class ColorAccuracyTest extends ImagineTestCase
{
    /**
     * @dataProvider cmykPrimaryColorsProvider
     */
    public function testCMYKPrimaryColorsAccuracy($name, $cmykValues, $expectedC, $expectedM, $expectedY, $expectedK)
    {
        $palette = new CMYKPalette();
        $color = $palette->color($cmykValues);

        $this->assertEquals($expectedC, $color->getCyan(), "Cyan value incorrect for $name");
        $this->assertEquals($expectedM, $color->getMagenta(), "Magenta value incorrect for $name");
        $this->assertEquals($expectedY, $color->getYellow(), "Yellow value incorrect for $name");
        $this->assertEquals($expectedK, $color->getKeyline(), "Black value incorrect for $name");
    }

    public static function cmykPrimaryColorsProvider()
    {
        return [
            'Pure Cyan' => ['Pure Cyan', [100, 0, 0, 0], 100, 0, 0, 0],
            'Pure Magenta' => ['Pure Magenta', [0, 100, 0, 0], 0, 100, 0, 0],
            'Pure Yellow' => ['Pure Yellow', [0, 0, 100, 0], 0, 0, 100, 0],
            'Pure Black' => ['Pure Black', [0, 0, 0, 100], 0, 0, 0, 100],
            'White' => ['White', [0, 0, 0, 0], 0, 0, 0, 0],
            'Mid Gray' => ['Mid Gray', [0, 0, 0, 50], 0, 0, 0, 50],
        ];
    }

    /**
     * @dataProvider rgbPrimaryColorsProvider
     */
    public function testRGBPrimaryColorsAccuracy($name, $rgbValues, $expectedR, $expectedG, $expectedB)
    {
        $palette = new RGBPalette();
        $color = $palette->color($rgbValues);

        $this->assertEquals($expectedR, $color->getRed(), "Red value incorrect for $name");
        $this->assertEquals($expectedG, $color->getGreen(), "Green value incorrect for $name");
        $this->assertEquals($expectedB, $color->getBlue(), "Blue value incorrect for $name");
    }

    public static function rgbPrimaryColorsProvider()
    {
        return [
            'Pure Red' => ['Pure Red', [255, 0, 0], 255, 0, 0],
            'Pure Green' => ['Pure Green', [0, 255, 0], 0, 255, 0],
            'Pure Blue' => ['Pure Blue', [0, 0, 255], 0, 0, 255],
            'Black' => ['Black', [0, 0, 0], 0, 0, 0],
            'White' => ['White', [255, 255, 255], 255, 255, 255],
            'Mid Gray' => ['Mid Gray', [128, 128, 128], 128, 128, 128],
        ];
    }

    public function testCMYKColorConsistency()
    {
        $palette = new CMYKPalette();

        // Create same color twice
        $color1 = $palette->color([50, 25, 75, 10]);
        $color2 = $palette->color([50, 25, 75, 10]);

        // Should be the same object (color caching)
        $this->assertSame($color1, $color2, 'Color caching should return same object');

        // Values should match
        $this->assertEquals(50, $color1->getCyan());
        $this->assertEquals(25, $color1->getMagenta());
        $this->assertEquals(75, $color1->getYellow());
        $this->assertEquals(10, $color1->getKeyline());
    }

    public function testRGBColorConsistency()
    {
        $palette = new RGBPalette();

        // Create same color twice
        $color1 = $palette->color([128, 64, 192]);
        $color2 = $palette->color([128, 64, 192]);

        // Should be the same object (color caching)
        $this->assertSame($color1, $color2, 'Color caching should return same object');

        // Values should match
        $this->assertEquals(128, $color1->getRed());
        $this->assertEquals(64, $color1->getGreen());
        $this->assertEquals(192, $color1->getBlue());
    }

    public function testCMYKColorStringRepresentation()
    {
        $palette = new CMYKPalette();
        $color = $palette->color([100, 50, 25, 10]);

        $expected = 'cmyk(100%, 50%, 25%, 10%)';
        $this->assertEquals($expected, (string) $color);
    }

    public function testRGBColorHexRepresentation()
    {
        $palette = new RGBPalette();
        $color = $palette->color([255, 128, 64]);

        $expected = '#ff8040';
        $this->assertEquals($expected, (string) $color);
    }
}
